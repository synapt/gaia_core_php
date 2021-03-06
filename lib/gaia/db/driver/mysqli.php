<?php
namespace Gaia\DB\Driver;
use Gaia\DB\Connection;
use Gaia\DB\Transaction;

class MySQLi extends \MySQLi implements \Gaia\DB\Iface {
    
    protected $lock = FALSE;
    protected $txn = FALSE;
    
    public function execute( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->query( $this->prep_args( $query, $args ) );
    }
    
    public function query( $query, $mode = MYSQLI_STORE_RESULT ){
        if( $this->lock ) return FALSE;
        $res = parent::query( $query, $mode );
        if( $res ) return $res;
        if( $this->txn ) {
            Transaction::block();
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function multi_query( $query ){
        if( $this->lock ) return FALSE;
        $res = parent::multi_query( $query );
        if( $res ) return $res;
        if( $this->txn ) {
            Transaction::block();
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function real_query( $query ){
        if( $this->lock ) return FALSE;
        $res = parent::real_query( $query );
        if( $res ) return $res;
        if( $this->txn ) {
            Transaction::block();
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function close(){
        Connection::remove( $this );
        if( $this->lock ) return FALSE;
        $rs = parent::close();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function prepare($query){
        trigger_error('unsupported method ' . __CLASS__ . '::' . __FUNCTION__, E_USER_ERROR);
        exit;
    }
    
    
    public function start( $auth = NULL ){
        if( $auth == Transaction::SIGNATURE){
            if( $this->lock ) return FALSE;
            $this->txn = TRUE;
            return parent::query('START TRANSACTION');
        }
        Transaction::start();
        if( ! Transaction::add($this) ) return FALSE;
        return TRUE;
    }
    
    public function autocommit($mode){
        return ( $mode ) ? $this->commit() : $this->start();
    }
    
    public function rollback($auth = NULL){
        if( $auth != Transaction::SIGNATURE) return Transaction::rollback();
        if( ! $this->txn ) return parent::query('ROLLBACK');
        if( $this->lock ) return TRUE;
        $rs = parent::query('ROLLBACK');
        $this->lock = TRUE;
        return $rs;
    }
    
    public function commit($auth = NULL){
        if( $auth != Transaction::SIGNATURE) return Transaction::commit();
        if( ! $this->txn ) return parent::query('COMMIT');
        if( $this->lock ) return FALSE;
        $res = parent::query('COMMIT');
        if( ! $res ) return $res;
        $this->txn = FALSE;
        return $res;
    }
    
    public function prep( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->prep_args( $query, $args );
    }

    public function prep_args($query, array $args) {
        if( ! $args || count( $args ) < 1 ) return $query;
        $conn = $this;
        return \Gaia\DB\Query::prepare( 
            $query, 
            $args, 
            function($v) use( $conn ){ return "'" . $conn->real_escape_string( $v ) . "'"; }
            );
    }
    
    public function isa( $name ){
        if( $this instanceof $name ) return TRUE;
        $name = strtolower( $name );
        if( $name == 'mysql' ) return TRUE;
        return FALSE;
    }
    
    public function hash(){
        return spl_object_hash( $this );
    }
    
    public function __toString(){
        @ $res ='(Gaia\DB\MySQLi object - ' . "\n" .
            '  [affected_rows] => ' . $this->affected_rows . "\n" .
            '  [client_info] => ' . $this->client_info . "\n" .
            '  [client_version] => ' . $this->client_version . "\n" .
            '  [connect_errno] => ' . $this->connect_errno . "\n" .
            '  [connect_error] => ' . $this->connect_error . "\n" .
            '  [errno] => ' . $this->errno . "\n" .
            '  [error] => ' . $this->error . "\n" .
            '  [field_count] => ' . $this->field_count . "\n" .
            '  [host_info] => ' . $this->host_info . "\n" .
            '  [info] => ' . $this->info . "\n" .
            '  [insert_id] => ' . $this->insert_id . "\n" .
            '  [server_info] => ' . $this->server_info . "\n" .
            '  [server_version] => ' . $this->server_version . "\n" .
            '  [sqlstate] => ' . $this->sqlstate . "\n" .
            '  [protocol_version] => ' . $this->protocol_version . "\n" .
            '  [thread_id] => ' . $this->thread_id . "\n" .
            '  [warning_count] => ' . $this->warning_count . "\n" .
            '  [lock] => ' .( $this->lock ? 'TRUE' : 'FALSE') . "\n" .
            '  [txn] => ' .( $this->txn ? 'TRUE' : 'FALSE') . "\n" .
            ')';
        return $res;
    }
    
    public function __get( $k ){
        if( $k == 'lock' ) return $this->lock;
        if( $k == 'txn' ) return $this->txn;
        return NULL;
    }
    
    public function __set( $k, $v ){
        if( $k == 'lock' ) return $this->lock = (bool) $v;
        if( $k == 'txn' ) return $this->txn = (bool) $v;
        return NULL;
    }
}
