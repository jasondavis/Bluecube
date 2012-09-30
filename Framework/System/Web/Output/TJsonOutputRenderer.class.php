<?php
class TJsonOutputRenderer implements IOutputRenderer
{
    public function render($data)
    {
        return TVar::toJSON($data);
    }
}