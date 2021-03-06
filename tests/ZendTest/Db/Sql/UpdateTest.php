<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Db
 */

namespace ZendTest\Db\Sql;

use Zend\Db\Sql\Update;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

class UpdateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Update
     */
    protected $update;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->update = new Update;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Zend\Db\Sql\Update::table
     */
    public function testTable()
    {
        $this->update->table('foo', 'bar');
        $this->assertEquals('foo', $this->readAttribute($this->update, 'table'));
    }

    /**
     * @covers Zend\Db\Sql\Update::set
     */
    public function testSet()
    {
        $this->update->set(array('foo' => 'bar'));
        $this->assertEquals(array('foo' => 'bar'), $this->readAttribute($this->update, 'set'));
    }

    /**
     * @covers Zend\Db\Sql\Update::where
     */
    public function testWhere()
    {
        $this->update->where('x = y');
        $this->update->where(array('foo > ?' => 5));
        $this->update->where(array('id' => 2));
        $this->update->where(array('a = b'), Where::OP_OR);
        $this->update->where(array('c1' => null));
        $this->update->where(array('c2' => array(1, 2, 3)));
        $this->update->where(array(new \Zend\Db\Sql\Predicate\IsNotNull('c3')));
        $where = $this->update->where;

        $predicates = $this->readAttribute($where, 'predicates');
        $this->assertEquals('AND', $predicates[0][0]);
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Expression', $predicates[0][1]);

        $this->assertEquals('AND', $predicates[1][0]);
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Expression', $predicates[1][1]);

        $this->assertEquals('AND', $predicates[2][0]);
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Operator', $predicates[2][1]);

        $this->assertEquals('OR', $predicates[3][0]);
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\Expression', $predicates[3][1]);

        $this->assertEquals('AND', $predicates[4][0]);
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\IsNull', $predicates[4][1]);

        $this->assertEquals('AND', $predicates[5][0]);
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\In', $predicates[5][1]);

        $this->assertEquals('AND', $predicates[6][0]);
        $this->assertInstanceOf('Zend\Db\Sql\Predicate\IsNotNull', $predicates[6][1]);

        $where = new Where;
        $this->update->where($where);
        $this->assertSame($where, $this->update->where);

        $test = $this;
        $this->update->where(function ($what) use ($test, $where) {
            $test->assertSame($where, $what);
        });
    }

    /**
     * @group ZF2-240
     * @covers Zend\Db\Sql\Update::where
     */
    public function testPassingMultipleKeyValueInWhereClause()
    {
        $update = clone $this->update;
        $update->table('table');
        $update->set(array('fld1' => 'val1'));
        $update->where(array('id1' => 'val1', 'id2' => 'val2'));
        $this->assertEquals('UPDATE "table" SET "fld1" = \'val1\' WHERE "id1" = \'val1\' AND "id2" = \'val2\'', $update->getSqlString());
    }

    /**
     * @covers Zend\Db\Sql\Update::prepareStatement
     */
    public function testPrepareStatement()
    {
        $mockDriver = $this->getMock('Zend\Db\Adapter\Driver\DriverInterface');
        $mockDriver->expects($this->any())->method('getPrepareType')->will($this->returnValue('positional'));
        $mockDriver->expects($this->any())->method('formatParameterName')->will($this->returnValue('?'));
        $mockAdapter = $this->getMock('Zend\Db\Adapter\Adapter', null, array($mockDriver));

        $mockStatement = $this->getMock('Zend\Db\Adapter\Driver\StatementInterface');
        $pContainer = new \Zend\Db\Adapter\ParameterContainer(array());
        $mockStatement->expects($this->any())->method('getParameterContainer')->will($this->returnValue($pContainer));

        $mockStatement->expects($this->at(1))
            ->method('setSql')
            ->with($this->equalTo('UPDATE "foo" SET "bar" = ?, "boo" = NOW() WHERE x = y'));

        $this->update->table('foo')
            ->set(array('bar' => 'baz', 'boo' => new Expression('NOW()')))
            ->where('x = y');

        $this->update->prepareStatement($mockAdapter, $mockStatement);
    }

    /**
     * @covers Zend\Db\Sql\Update::getSqlString
     */
    public function testGetSqlString()
    {
        $this->update->table('foo')
            ->set(array('bar' => 'baz', 'boo' => new Expression('NOW()'), 'bam' => null))
            ->where('x = y');

        $this->assertEquals('UPDATE "foo" SET "bar" = \'baz\', "boo" = NOW(), "bam" = NULL WHERE x = y', $this->update->getSqlString());
    }

    /**
     * @covers Zend\Db\Sql\Update::__clone
     */
    public function testCloneUpdate()
    {
        $update1 = clone $this->update;
        $update1->table('foo')
                ->set(array('bar' => 'baz'))
                ->where('x = y');

        $update2 = clone $this->update;
        $update2->table('foo')
            ->set(array('bar' => 'baz'))
            ->where(array(
                'id = ?'=>1
            ));
        $this->assertEquals('UPDATE "foo" SET "bar" = \'baz\' WHERE id = \'1\'', $update2->getSqlString());
    }

}
