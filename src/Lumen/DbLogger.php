<?php namespace Sofa\Revisionable\Lumen;

use Sofa\Revisionable\Logger;
use Illuminate\Database\ConnectionInterface;
use DateTime;

class DbLogger implements Logger
{
    /**
     * Custom database connection.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;



    /**
     * Default database connection.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $defaultConnection;

    /**
     * Revisions table name.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new DbLogger.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param string $table
     */
    public function __construct(ConnectionInterface $connection, $table)
    {
        $this->defaultConnection = $connection;
        $this->table             = $table;
    }

    /**
     * Log data revisions in the db.
     *
     * @param  string  $action
     * @param  string  $table
     * @param  int     $id
     * @param  array   $old
     * @param  array   $new
     * @param  string  $user
     * @return void
     */
    public function revisionLog($action, $table, $id, array $old = [], array $new = [], $user = null,array $old_diff = [],array $new_diff = [])
    {
        $user = $this->parseUser($user);

        $connection = $this->getCurrentConnection();

        if(method_exists ($connection,'getDateFormat')) {
            $format = $connection->getQueryGrammar()->getDateFormat();
            $currentDatetime = (new DateTime)->format($format);
        }else {
            $currentDatetime = new \MongoDB\BSON\UTCDateTime(time() * 1000);
        }

        $connection->table($this->table)->insert([
            'action'       => substr($action, 0, 255),
            'collection'   => substr($table, 0, 255),
            'document'       => substr($id, 0, 255),
            'old'          => $old_diff,
            'new'          => $new_diff,
            'updated_by'         => substr($user, 0, 255),
            'created_at'   => $currentDatetime,
        ]);

        $this->resetConnection();
    }



    /**
     * Set custom connection for the next log.
     *
     * @param  \Illuminate\Database\ConnectionInterface $connection
     * @return static
     */
    public function on(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Translate provided user to appropriate string.
     *
     * @param  mixed  $user
     * @return string
     */
    protected function parseUser($user)
    {
        return (is_string($user) || is_numeric($user)) ? $user : ' -- ';
    }

    /**
     * Return current connection instance to use for next log.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function getCurrentConnection()
    {
        return ($this->connection) ?: $this->defaultConnection;
    }

    /**
     * Reset custom connection.
     *
     * @return void
     */
    protected function resetConnection()
    {
        $this->connection = null;
    }

    /**
     * Get Server variable.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return string|array
     */
    protected function getFromServer($key, $default = null)
    {
        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }
}
