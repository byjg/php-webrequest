<?php

namespace ByJG\WebRequest;

use Closure;
use LogicException;
use Throwable;

/**
 * The result of HttpClient::sendAsync(), with the JavaScript Promise semantics.
 *
 * There is no event loop: the requests are transferred (all of them, in parallel, with curl_multi)
 * while await() is called on any promise, and the then/catch/finally callbacks run then.
 */
class HttpPromise
{
    protected HttpPromiseState $state = HttpPromiseState::Pending;
    protected mixed $value = null;

    /** @var Closure[] Called once the promise settles */
    protected array $callbacks = [];

    /**
     * @internal Use HttpClient::sendAsync(), resolve() or reject()
     */
    public function __construct()
    {
    }

    public static function resolve(mixed $value): HttpPromise
    {
        $promise = new HttpPromise();
        $promise->fulfill($value);
        return $promise;
    }

    public static function reject(Throwable $reason): HttpPromise
    {
        $promise = new HttpPromise();
        $promise->settle(HttpPromiseState::Rejected, $reason);
        return $promise;
    }

    /**
     * Fulfill the promise, or follow the promise passed, as a JavaScript resolve()
     *
     * @internal
     */
    public function fulfill(mixed $value): void
    {
        if ($value === $this) {
            $this->settle(HttpPromiseState::Rejected, new LogicException('A promise cannot be resolved with itself'));
            return;
        }

        if ($value instanceof HttpPromise) {
            $value->onSettle(fn() => $this->settle($value->state, $value->value));
            return;
        }

        $this->settle(HttpPromiseState::Fulfilled, $value);
    }

    /**
     * @internal
     */
    public function settle(HttpPromiseState $state, mixed $value): void
    {
        if ($this->state !== HttpPromiseState::Pending) {
            return;
        }

        $this->state = $state;
        $this->value = $value;

        $callbacks = $this->callbacks;
        $this->callbacks = [];
        foreach ($callbacks as $callback) {
            $callback();
        }
    }

    protected function onSettle(Closure $callback): void
    {
        if ($this->state === HttpPromiseState::Pending) {
            $this->callbacks[] = $callback;
        } else {
            $callback();
        }
    }

    public function getState(): HttpPromiseState
    {
        return $this->state;
    }

    /**
     * A handler's return value fulfills the returned promise (a handled rejection recovers),
     * a handler that throws rejects it, and a missing handler passes the value or the reason through.
     */
    public function then(?Closure $onFulfilled = null, ?Closure $onRejected = null): HttpPromise
    {
        $next = new HttpPromise();

        $this->onSettle(function () use ($next, $onFulfilled, $onRejected) {
            $handler = $this->state === HttpPromiseState::Fulfilled ? $onFulfilled : $onRejected;
            if ($handler === null) {
                $next->settle($this->state, $this->value);
                return;
            }

            try {
                $next->fulfill($handler($this->value));
            } catch (Throwable $ex) {
                $next->settle(HttpPromiseState::Rejected, $ex);
            }
        });

        return $next;
    }

    public function catch(Closure $onRejected): HttpPromise
    {
        return $this->then(null, $onRejected);
    }

    /**
     * The callback gets no argument and its return value is ignored: the returned promise settles
     * with the original value or reason, unless the callback throws.
     */
    public function finally(Closure $onFinally): HttpPromise
    {
        return $this->then(
            function (mixed $value) use ($onFinally) {
                $onFinally();
                return $value;
            },
            function (Throwable $reason) use ($onFinally) {
                $onFinally();
                throw $reason;
            }
        );
    }

    /**
     * Transfer the pending requests until this promise settles.
     *
     * @return mixed The value, e.g. the ResponseInterface of sendAsync()
     * @throws Throwable The reason of a rejected promise, e.g. a NetworkException
     */
    public function await(): mixed
    {
        while ($this->state === HttpPromiseState::Pending) {
            if (!CurlMultiLoop::getInstance()->tick()) {
                throw new LogicException('The promise cannot settle: there is no request in progress');
            }
        }

        if ($this->state === HttpPromiseState::Rejected) {
            /** @var Throwable */
            throw $this->value;
        }

        return $this->value;
    }

    /**
     * Fulfilled with the values in the order of the promises (keys kept) when all of them are fulfilled,
     * or rejected with the reason of the first one rejected.
     */
    public static function all(HttpPromise ...$promises): HttpPromise
    {
        $all = new HttpPromise();
        $values = array_fill_keys(array_keys($promises), null);
        $pending = count($promises);

        if ($pending === 0) {
            $all->fulfill([]);
        }

        foreach ($promises as $key => $promise) {
            $promise->then(
                function (mixed $value) use ($all, &$values, &$pending, $key) {
                    $values[$key] = $value;
                    if (--$pending === 0) {
                        $all->fulfill($values);
                    }
                },
                fn(Throwable $reason) => $all->settle(HttpPromiseState::Rejected, $reason)
            );
        }

        return $all;
    }

    /**
     * Settled as the first of the promises that settles
     */
    public static function race(HttpPromise ...$promises): HttpPromise
    {
        $race = new HttpPromise();

        foreach ($promises as $promise) {
            $promise->onSettle(fn() => $race->settle($promise->state, $promise->value));
        }

        return $race;
    }
}
