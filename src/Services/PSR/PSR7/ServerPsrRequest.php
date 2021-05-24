<?php

namespace Prokl\ServiceProvider\Services\PSR\PSR7;

use GuzzleHttp\Psr7\UploadedFile;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class ServerPsrRequest
 * @package Prokl\ServiceProvider\Services\Convertor
 */
class ServerPsrRequest extends PsrRequest implements ServerRequestInterface
{
    /**
     * @return array
     */
    public function getServerParams(): array
    {
        return $this->request->getServer()->toArray();
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams(): array
    {
        return $this->request->getCookieList()->toArray();
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getCookieList()->setValues($cookies);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams(): array
    {
        return $this->request->getQueryList()->toArray();
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query): ServerPsrRequest
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getQueryList()->setValues($query);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles()
    {
        return array_map(function (array $file) {
            if (is_array($file['tmp_name'])) {
                $result = [];
                for ($i = 0; $i < count($file['tmp_name']); $i++) {
                    $result[$i] = new UploadedFile(
                        $file['tmp_name'][$i],
                        (int)$file['size'][$i],
                        (int)$file['error'][$i],
                        $file['name'][$i],
                        $file['type'][$i]
                    );
                }

                return $result;
            }
            return new UploadedFile(
                $file['tmp_name'],
                (int) $file['size'],
                (int) $file['error'],
                $file['name'],
                $file['type']
            );
        }, $this->request->getFileList()->toArray());
    }

    /**
     * @return array
     */
    private function getFileList(): array
    {
        $fileList = [];
        foreach ($this->request->getFileList() as $key => $file) {
            foreach ($file as $k => $value) {
                if (is_array($value)) {
                    foreach ($value as $i => $v) {
                        $fileList[$key][$i][$k] = $v;
                    }
                } else {
                    $fileList[$key][$k] = $v;
                }
            }
        }

        return $fileList;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getFileList()->setValues($uploadedFiles);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        return $this->request->getPostList()->toArray();
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        $newRequest = $this->getClonedRequest();
        $newRequest->getPostList()->setValues($data);

        return new static($newRequest, $this->httpVersion, $this->body, $this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($attribute, $default = null)
    {
        if (false === array_key_exists($attribute, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($attribute, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($attribute): ServerRequestInterface
    {
        if (false === array_key_exists($attribute, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$attribute]);

        return $new;
    }
}
