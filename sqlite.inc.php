<?
class SQLiteSession extends SQLite3{
// session-lifetime
public  function __construct($filename='http_sessions.db'){
        $this->savePath = ini_get('session.save_path'). DIRECTORY_SEPARATOR .$filename;
        $this->dbHandle = parent::open($this->savePath);}

public  function _open($save_path,$session_id){
        return true;}

public  function _close(){
        // close database-connection
        return parent::close();}

public  function _read($session_id){
        // fetch session-data
        $stmt=parent::prepare('select session_data
                                 from http_sessions
                                where session_id=:session_id');
        if(!$stmt){$this->init();} 
        $stmt->bindValue(':session_id', $session_id, SQLITE3_TEXT);
        $res=$stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        return $row['session_data'];}

public  function _write($session_id,$session_data) {
        $stmt=parent::prepare('insert or replace into http_sessions (session_id,session_data,alive,process_id) 
                               values (:session_id,:session_data,datetime(),NULL)');
        $stmt->bindValue(':session_id', $session_id, SQLITE3_TEXT);
        $stmt->bindValue(':session_data', $session_data, SQLITE3_TEXT);
        $stmt->execute();
        return true;}

public  function _destroy($session_id) {
        // delete session-data
        $stmt=parent::prepare("delete
                                 from http_sessions
                                where id=:session_id");
        $stmt->bindValue(':session_id', $session_id, SQLITE3_TEXT);
        $stmt->execute();
        return true;}

public  function _gc($maxlifetime) {
        $stmt=parent::prepare("delete
                                 from http_sessions
                                 where datetime())-strftime('%s',alive)>:lifetime");
        $stmt->bindValue(':lifetime', ini_get('session.gc_maxlifetime'), SQLITE3_INTEGER);
        $stmt->execute();
        return parent::changes();}

private function init(){
        $db = new SqLiteSession();
        $db->exec('CREATE TABLE http_sessions (session_id text, session_data text, alive date, process_id int, PRIMARY KEY (session_id))');
        $db->close();}
}
$hSession = new SQLiteSession('http_sessions.db');
if (! session_set_save_handler(array(&$hSession, '_open'),
                               array(&$hSession, '_close'),
                               array(&$hSession, '_read'),
                               array(&$hSession, '_write'),
                               array(&$hSession, '_destroy'),
                               array(&$hSession, '_gc'))) {
    trigger_error('SQLiteSession: session_set_save_handler() failed', E_USER_ERROR);}
?>