<?php

namespace ByJG\WebRequest;

enum HttpPromiseState
{
    case Pending;
    case Fulfilled;
    case Rejected;
}
