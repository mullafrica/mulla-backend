<?php

namespace App\Traits;

trait UniqueId {
    protected function uuid() {
        return substr(md5(time()), 0, 64);
    }

    protected function uuid_ag()
    {
        return substr(md5(time()), 0, 6);
    }

    protected function uuid_ag2()
    {
        return substr(md5(microtime()), 0, 6);
    }

    protected function uuid12()
    {
        return substr(md5(microtime()), 0, 12);
    }

    protected function uuid16()
    {
        return substr(md5(microtime()), 0, 16);
    }
}

