<?php

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    /**
     * @inheritdoc
     */
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }
}
