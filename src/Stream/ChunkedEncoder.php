<?php
namespace Icicle\Http\Stream;

use Icicle\Stream\Stream;

class ChunkedEncoder extends Stream
{
    /**
     * @param   string $data
     * @param   bool $end
     *
     * @return  \Icicle\Promise\PromiseInterface
     */
    protected function send($data, $end = false)
    {
        $length = strlen($data);

        if ($length) {
            $data = sprintf("%x\r\n%s\r\n", $length, $data);
        }

        if ($end) {
            $data .= "0\r\n\r\n";
        }

        return parent::send($data, $end);
    }
}
