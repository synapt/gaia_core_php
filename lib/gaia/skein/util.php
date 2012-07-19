<?php
namespace Gaia\Skein;
use Gaia\Exception;
use Gaia\Time;

class Util {
    
    protected static $current_shard_generator;
    
    public static function setCurrentShardGenerator( \Closure $c ){
        self::$current_shard_generator = $c;
    }
    
    public static function currentShard(){
        if( ! isset( self::$current_shard_generator ) ) return date('Ym', Time::now());
        $c = self::$current_shard_generator;
        return $c(); 
    }
    
    public static function validateIds( array $shard_sequences, array $ids ){
        $search = array();
        foreach( $ids as $id ){
            list( $shard, $sequence ) = Util::parseId( $id );
            if( ! isset( $shard_sequences[ $shard ] ) ) continue;
            if ( $sequence < 1 ) continue;
            if( $sequence > $shard_sequences[ $shard ] ) continue;
            $search[ $id ] = 1;
        }
        return array_keys( $search );
    }
    
    
    
    public static function count( array $shard_sequences ){
        $ct = 0;
        foreach( $shard_sequences as $shard=>$sequence ){
            $ct += $sequence;
        }
        return $ct;
    }
    
    public static function ascending( array $shard_sequences, $limit = 1000, $start_after = NULL ){
         ksort( $shard_sequences );
         if( $start_after === NULL ) {
            if( count( $shard_sequences ) < 1 ) return array();
            foreach( $shard_sequences as $shard => $sequence ) break;
            $start_after = self::composeId( $shard, 1 );
         }
         list( $start_shard, $start_sequence ) = self::parseId( $start_after );
         
         $result = array();
         
         if( $start_shard === NULL || $start_sequence === NULL ) return array();
         
         foreach( $shard_sequences as $shard => $sequence ){
            if( $shard < $start_shard ) continue;
            $pos = 1;
            if( $shard == $start_shard && $sequence > $start_sequence ) $pos = $start_sequence;
            while( $sequence >= $pos && $limit > 0) {
                $result[] = self::composeId( $shard, $pos++);
                $limit--;
            }
            if( $limit < 1 ) break;
         }
         return $result;
    }
    
    
    public static function descending( array $shard_sequences, $limit = 1000, $start_after = NULL ){
        krsort( $shard_sequences );
        if( $start_after === NULL ) {
            if( count( $shard_sequences ) < 1 ) return array();
            foreach( $shard_sequences as $shard => $sequence ) break;
            $start_after = self::composeId( $shard, $sequence + 1);
         }
         list( $start_shard, $start_sequence ) = self::parseId( $start_after );
         
         $result = array();
         
         if( $start_shard === NULL || $start_sequence === NULL ) return array();
         foreach( $shard_sequences as $shard => $sequence ){
            if( $shard > $start_shard ) continue;
            if( $shard == $start_shard && $sequence > $start_sequence ) $sequence = $start_sequence;
            while( $sequence > 0 && $limit > 0) {
                $result[] = self::composeId( $shard, $sequence--);
                $limit--;
            }
            if( $limit < 1 ) break;
         }
         return $result;
    }
    
    public static function filter( Iface $core, \Closure $cb, $method = 'ascending', $start_after = NULL ){
    
        if( $method != 'ascending' ) $method = 'descending';
        $id_chunk_size = 1000;
        $get_chunk_size = 100;
        $ct = 0;
        do {
            $ids = $core->$method( $id_chunk_size, $start_after );
            $ct = count( $ids );
            if( $ct < 1 ) return;
            foreach( array_chunk( $ids, $get_chunk_size) as $i ){
                foreach( $core->get( $i ) as $id => $data ){
                    $res = $cb( $id, $data );
                    if( $res === FALSE ) return;
                }
            }
        
        } while( $ct >= $id_chunk_size);
    }
    
    public static function parseId( $id, $validate = TRUE ){
        $id = (string) $id;
        $pattern = '#^([0-9]{1,11})\-([0-9]{11})$#';
        if( preg_match( $pattern, $id, $matches ) ){
            $shard = ltrim($matches[1], '0');
            $sequence = ltrim($matches[2], '0');
            if( $shard && $sequence ) return array( $shard, $sequence );
        }
        if( $validate ) {
            throw new Exception('invalid id', $id );
        }
        return array(NULL, NULL);
    }
    
    public static function parseIds( array $ids ){
        $info = array();
        foreach( $ids as $id ){
            list( $shard, $sequence ) = Util::parseId( $id );
            if( ! $shard || ! $sequence ) continue;
            if( ! isset( $info[ $shard ] ) ) $info[ $shard ] = array();
            $info[ $shard ][] = $sequence;
        }
        return $info;
    }
    
    
    public static function composeId( $shard, $sequence ){
        if( ! ctype_digit( strval( $shard ) ) || ! ctype_digit( strval( $sequence ) ) ) return NULL;
        return $shard . '-' . str_pad($sequence, 11, '0', STR_PAD_LEFT);
    }
    
}
