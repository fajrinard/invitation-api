<?php

namespace Core\Model;

use ArrayAccess;
use ArrayIterator;
use Closure;
use Core\Facades\App;
use Countable;
use Exception;
use IteratorAggregate;
use JsonSerializable;
use ReturnTypeWillChange;
use Traversable;

/**
 * Representasi table database.
 * 
 * @method static \Core\Model\Query with(string|array $relational)
 * @method static \Core\Model\Query where(string $column, mixed $value, string $statment = '=', string $agr = 'AND')
 * @method static \Core\Model\Query whereNull(string $column, string $agr = 'AND')
 * @method static \Core\Model\Query whereIn(string $column, array|Model $value, string $agr = 'AND')
 * @method static \Core\Model\Query whereNotIn(string $column, array|Model $value, string $agr = 'AND')
 * @method static \Core\Model\Query whereNotNull(string $column, string $agr = 'AND')
 * @method static \Core\Model\Query join(string $table, string $column, string $refers, string $param = '=', string $type = 'INNER')
 * @method static \Core\Model\Query leftJoin(string $table, string $column, string $refers, string $param = '=')
 * @method static \Core\Model\Query rightJoin(string $table, string $column, string $refers, string $param = '=')
 * @method static \Core\Model\Query fullJoin(string $table, string $column, string $refers, string $param = '=')
 * @method static \Core\Model\Query orderBy(string $name, string $order = 'ASC')
 * @method static \Core\Model\Query groupBy(string|array $param)
 * @method static \Core\Model\Query limit(int $param)
 * @method static \Core\Model\Query offset(int $param)
 * @method static \Core\Model\Query select(string|array $param)
 * @method static \Core\Model\Query max(string $name)
 * @method static \Core\Model\Query min(string $name)
 * @method static \Core\Model\Query avg(string $name)
 * @method static \Core\Model\Query sum(string $name)
 * @method static \Core\Model\Query id(mixed $id, string|null $where = null)
 * @method static \Core\Model\Model get()
 * @method static \Core\Model\Model first()
 * @method static \Core\Model\Model find(mixed $id, string|null $where = null)
 * @method static \Core\Model\Model create(array $data)
 * @method static int destroy(int $id)
 * @method static int update(array $data)
 * @method static int delete()
 * 
 * @see \Core\Model\Query
 *
 * @class Model
 * @package \Core\Model
 */
abstract class Model implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * Nama tabelnya.
     * 
     * @var string $table
     */
    protected $table;

    /**
     * Nama dari primaryKey.
     * 
     * @var string $primaryKey
     */
    protected $primaryKey = 'id';

    /**
     * Tipe dari dates.
     * 
     * @var array $dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Casting a attribute.
     * 
     * @var array $casts
     */
    protected $casts = [];

    /**
     * Attributes hasil query.
     * 
     * @var array $attributes
     */
    protected $attributes;

    /**
     * Mempunyai satu dari.
     * 
     * @param string $model
     * @param string $foreign_key
     * @param Closure|string|null $local_key
     * @param Closure|null $callback
     * @return BelongsTo
     */
    protected function belongsTo(string $model, string $foreign_key, Closure|string|null $local_key = null, Closure|null $callback = null): BelongsTo
    {
        if ($local_key instanceof Closure) {
            return new BelongsTo($model, $this->primaryKey, $foreign_key, $local_key);
        }

        return new BelongsTo($model, $local_key ? $local_key : $this->primaryKey, $foreign_key, $callback);
    }

    /**
     * Mempunyai banyak dari.
     * 
     * @param string $model
     * @param string $foreign_key
     * @param Closure|string|null $local_key
     * @param Closure|null $callback
     * @return HasMany
     */
    protected function hasMany(string $model, string $foreign_key, Closure|string|null $local_key = null, Closure|null $callback = null): HasMany
    {
        if ($local_key instanceof Closure) {
            return new HasMany($model, $foreign_key, $this->primaryKey, $local_key);
        }

        return new HasMany($model, $foreign_key, $local_key ? $local_key : $this->primaryKey, $callback);
    }

    /**
     * Set attributenya.
     *
     * @param array $data
     * @return Model
     */
    public function setAttribute(array $data): Model
    {
        $this->attributes = $data;
        return $this;
    }

    /**
     * Set nama tabelnya.
     *
     * @param string $name
     * @return Model
     */
    public function setTable(string $name): Model
    {
        $this->table = $name;
        return $this;
    }

    /**
     * Hapus attribute dates dan primaryKey.
     *
     * @return Model
     */
    public function clearForDB(): Model
    {
        $this->dates = [];
        $this->primaryKey = null;
        return $this;
    }

    /**
     * Ambil attribute.
     *
     * @return array
     */
    public function attribute(): array
    {
        return $this->attributes ?? [];
    }

    /**
     * Ubah objek agar bisa iterasi.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->attribute());
    }

    /**
     * Ubah objek ke json secara langsung.
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return $this->attribute();
    }

    /**
     * Set nilai ke attribute.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    /**
     * Apakah ada di attribute?.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Hapus datanya lewat unset.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Dapatkan nilai dari attributenya.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    /**
     * Hitung jumlah arraynya.
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->attribute());
    }

    /**
     * Ubah objek ke array.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->attribute();
    }

    /**
     * Kebalikan dari serialize.
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->attributes = $data;
    }

    /**
     * Eksport to json.
     *
     * @return string|bool
     */
    public function toJson(): string|bool
    {
        return json_encode($this->attribute(), 0, 1024);
    }

    /**
     * Eksport to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->map(function ($value): mixed {
            return is_object($value) ? get_object_vars($value) : $value;
        })->attribute();
    }

    /**
     * Get primaryKey.
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Error dengan fungsi.
     *
     * @param Closure|null $fn
     * @return mixed
     */
    public function fail(Closure|null $fn = null): mixed
    {
        if ($this->attributes) {
            return $this;
        }

        if ($fn instanceof Closure) {
            return App::get()->resolve($fn);
        }

        return false;
    }

    /**
     * Refresh attributnya.
     *
     * @return Model
     */
    public function refresh(): Model
    {
        return $this->find($this->__get($this->primaryKey));
    }

    /**
     * Save perubahan pada attribute dengan primarykey.
     *
     * @return int
     * 
     * @throws Exception
     */
    public function save(): int
    {
        if (empty($this->primaryKey) || empty($this->__get($this->primaryKey))) {
            throw new Exception('Nilai primary key tidak ada !');
        }

        return $this->id($this->__get($this->primaryKey))->update($this->except($this->primaryKey)->attribute());
    }

    /**
     * Iterasikan data dari attribute.
     * 
     * @param Closure $fn
     * @return Model
     */
    public function map(Closure $fn): Model
    {
        foreach ($this->attribute() as $key => $value) {
            $this->attributes[$key] = $fn($value, $key);
        }

        return $this;
    }

    /**
     * Ambil sebagian dari attribute.
     * 
     * @param array|string $only
     * @return Model
     */
    public function only(array|string $only): Model
    {
        if (is_string($only)) {
            $only = array($only);
        }

        $temp = [];
        foreach ($only as $ol) {
            $temp[$ol] = $this->__get($ol);
        }

        $this->attributes = $temp;
        return $this;
    }

    /**
     * Ambil kecuali dari attribute.
     * 
     * @param array|string $except
     * @return Model
     */
    public function except(array|string $except): Model
    {
        if (is_string($except)) {
            $except = array($except);
        }

        $temp = [];
        foreach ($this->attribute() as $key => $value) {
            if (!in_array($key, $except)) {
                $temp[$key] = $value;
            }
        }

        $this->attributes = $temp;
        return $this;
    }

    /**
     * Ambil nilai dari attribute.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return ($this->__isset($name)) ? $this->attributes[$name] : null;
    }

    /**
     * Isi nilai ke model ini.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     * 
     * @throws Exception
     */
    public function __set(string $name, mixed $value): void
    {
        if ($this->primaryKey == $name && $this->__isset($name)) {
            throw new Exception('Nilai primary key tidak bisa di ubah !');
        }

        $this->attributes[$name] = $value;
    }

    /**
     * Cek nilai dari attribute.
     * 
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Panggil method secara static.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        $class = get_called_class();
        return (new $class)->__call($method, $parameters);
    }

    /**
     * Panggil method secara object.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        return App::get()->singleton(Query::class)
            ->setTable($this->table)
            ->setDates($this->dates)
            ->setPrimaryKey($this->primaryKey)
            ->setCasts($this->casts)
            ->setObject(get_called_class())
            ->{$method}(...$parameters);
    }
}
