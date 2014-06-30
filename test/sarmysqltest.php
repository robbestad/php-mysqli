<?php
/**
 * Unit Test Example
 *
 * @author Sven Anders Robbestad <robbestad@gmail.com>
 */
namespace svenanders\tests;

require_once __DIR__ . '/../src/svenanders/Mysql.php';

use svenanders\Mysql as sarmysql;

/**
 * ParserTest class test case
 *
 * @author Sven Anders Robbestad <robbestad@gmail.com>
 */
class MysqlTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Can Connect to database
     */
    public function testCanConnectToDatabase()
    {
        $conn = new sarmysql();
        $this->assertNotNull($conn);
    }

    /**
     * Can Manipulate Rows
     * Adds a string
     * Modifies it
     * Deletes it
     */
    public function testCanLogin()
    {
        $conn = new sarmysql();
        $id = $conn->select("user",array("ID"),"WHERE Login = ? ", array("administrator"));

        // Assert that the entry is inserted
        $this->assertNotEmpty((int)$id);
    }

}
