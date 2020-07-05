<?php

header("content-type", "text-plain");
sleep(1 + rand(0, 3));
echo $_REQUEST["param"];
