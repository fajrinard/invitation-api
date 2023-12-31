<?php

namespace Core\Http;

use Core\File\File;
use Core\Valid\Validator;
use Exception;

/**
 * Request yang masuk.
 *
 * @class Request
 * @package \Core\Http
 */
class Request
{
    /**
     * Data dari global request.
     * 
     * @var array $requestData
     */
    private $requestData;

    /**
     * Data dari global server.
     * 
     * @var array $serverData
     */
    private $serverData;

    /**
     * Object validator.
     * 
     * @var Validator $validator
     */
    private $validator;

    /**
     * Init objek.
     * 
     * @return void
     */
    public function __construct()
    {
        @$_REQUEST = [...@$_REQUEST ?? [], ...@json_decode(strval(file_get_contents('php://input')), true, 1024) ?? []];
        $this->requestData = [...@$_REQUEST, ...@$_FILES ?? []];
        $this->serverData = @$_SERVER ?? [];
    }

    /**
     * Cek apakah ada error.
     * 
     * @return void
     */
    private function fails(): void
    {
        if ($this->validator->fails()) {
            session()->set('old', $this->all());
            session()->set('error', $this->validator->failed());
            respond()->redirect(session()->get('__oldroute', '/'));
        }
    }

    /**
     * Ambil nilai dari request ini.
     *
     * @param string|null $name
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get(string|null $name = null, mixed $defaultValue = null): mixed
    {
        if ($name === null) {
            return $this->requestData;
        }

        return $this->requestData[$name] ?? $defaultValue;
    }

    /**
     * Ambil nilai dari request server ini.
     *
     * @param string|null $name
     * @param mixed $defaultValue
     * @return mixed
     */
    public function server(string|null $name = null, mixed $defaultValue = null): mixed
    {
        if ($name === null) {
            return $this->serverData;
        }

        return $this->serverData[$name] ?? $defaultValue;
    }

    /**
     * Http method.
     *
     * @return string
     */
    public function method(): string
    {
        return strtoupper($this->server('REQUEST_METHOD'));
    }

    /**
     * Dapatkan ipnya.
     *
     * @return string|null
     */
    public function ip(): string|null
    {
        if ($this->server('HTTP_CLIENT_IP')) {
            return $this->server('HTTP_CLIENT_IP');
        }

        if ($this->server('HTTP_X_FORWARDED_FOR')) {
            $ipList = explode(',', $this->server('HTTP_X_FORWARDED_FOR'));
            foreach ($ipList as $ip) {
                if (!empty($ip)) {
                    return $ip;
                }
            }
        }

        if ($this->server('HTTP_X_FORWARDED')) {
            return $this->server('HTTP_X_FORWARDED');
        }

        if ($this->server('HTTP_X_CLUSTER_CLIENT_IP')) {
            return $this->server('HTTP_X_CLUSTER_CLIENT_IP');
        }

        if ($this->server('HTTP_FORWARDED_FOR')) {
            return $this->server('HTTP_FORWARDED_FOR');
        }

        if ($this->server('HTTP_FORWARDED')) {
            return $this->server('HTTP_FORWARDED');
        }

        if ($this->server('REMOTE_ADDR')) {
            return $this->server('REMOTE_ADDR');
        }

        return null;
    }

    /**
     * Cek apakah ajax atau fetch?.
     *
     * @return string|bool
     */
    public function ajax(): string|bool
    {
        if (str_contains(strtolower($this->server('HTTP_ACCEPT')), 'json')) {
            if ($this->server('HTTP_TOKEN')) {
                return $this->server('HTTP_TOKEN');
            }

            return true;
        }

        if ($this->server('CONTENT_TYPE') && $this->server('HTTP_COOKIE') && $this->server('HTTP_TOKEN')) {
            return $this->server('HTTP_TOKEN');
        }

        return false;
    }

    /**
     * Tampilkan error secara manual.
     *
     * @param array|Validator $error
     * @return void
     * 
     * @throws Exception
     */
    public function throw(array|Validator $error): void
    {
        if ($error instanceof Validator) {
            if ($this->validator instanceof Validator) {
                throw new Exception('Terdapat 2 object validator !');
            }

            $this->validator = $error;
        } else {
            $this->validator->throw($error);
        }

        $this->fails();
    }

    /**
     * Validasi request yang masuk.
     *
     * @param array $params
     * @return array
     */
    public function validate(array $params = []): array
    {
        $key = array_keys($params);

        $this->validator = Validator::make($this->only($key), $params);
        $this->fails();

        foreach ($key as $k) {
            $this->__set($k, $this->validator->get($k));
        }

        return $this->only($key);
    }

    /**
     * Ambil file yang masuk.
     *
     * @param string $name
     * @return File
     */
    public function file(string $name): File
    {
        $file = new File($this);
        $file->getFromRequest($name);
        return $file;
    }

    /**
     * Ambil semua nilai dari request ini.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->get();
    }

    /**
     * Ambil sebagian dari request.
     * 
     * @param array $only
     * @return array
     */
    public function only(array $only): array
    {
        $temp = [];
        foreach ($only as $ol) {
            $temp[$ol] = $this->__get($ol);
        }

        return $temp;
    }

    /**
     * Ambil kecuali dari request.
     * 
     * @param array $except
     * @return array
     */
    public function except(array $except): array
    {
        $temp = [];
        foreach ($this->all() as $key => $value) {
            if (!in_array($key, $except)) {
                $temp[$key] = $value;
            }
        }

        return $temp;
    }

    /**
     * Ambil nilai dari request ini.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->__isset($name) ? $this->requestData[$name] : null;
    }

    /**
     * Isi nilai ke request ini.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->requestData[$name] = $value;
    }

    /**
     * Cek nilai dari request ini.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->requestData[$name]);
    }
}
