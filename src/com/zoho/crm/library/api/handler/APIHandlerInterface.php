<?php

interface APIHandlerInterface
{
    public function getRequestMethod();

    public function getUrlPath();

    public function getRequestBody();

    public function getRequestHeaders();

    public function getRequestParams();
}


