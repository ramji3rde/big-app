<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../../../tools/helpers/bookstore/BookstoreTestBase.php';
require_once dirname(__FILE__) . '/../../../tools/helpers/bookstore/BookstoreDataPopulator.php';

/**
 * Test class for PropelQuery
 *
 * @author     Francois Zaninotto
 * @version    $Id: PropelQueryTest.php 1351 2009-12-04 22:05:01Z francois $
 * @package    runtime.query
 */
class PropelQueryTest extends BookstoreTestBase
{
    public function testFrom()
    {
        $q = PropelQuery::from('Book');
        $expected = new BookQuery();
        $this->assertEquals($expected, $q, 'from() returns a Model query instance based on the model name');

        $q = PropelQuery::from('Book b');
        $expected = new BookQuery();
        $expected->setModelAlias('b');
        $this->assertEquals($expected, $q, 'from() sets the model alias if found after the blank');

        $q = PropelQuery::from('myBook');
        $expected = new myBookQuery();
        $this->assertEquals($expected, $q, 'from() can find custom query classes');

        try {
            $q = PropelQuery::from('Foo');
            $this->fail('PropelQuery::from() throws an exception when called on a non-existing query class');
        } catch (PropelException $e) {
            $this->assertTrue(true, 'PropelQuery::from() throws an exception when called on a non-existing query class');
        }
    }

    public function testQuery()
    {
        BookstoreDataPopulator::depopulate();
        BookstoreDataPopulator::populate();

        $book = PropelQuery::from('Book b')
            ->where('b.Title like ?', 'Don%')
            ->orderBy('b.ISBN', 'desc')
            ->findOne();
        $this->assertTrue($book instanceof Book);
        $this->assertEquals('Don Juan', $book->getTitle());

    }

    /**
     * testFilterById
     *
     * Various test for filterById functions
     * Id's are autoincrement so we have to use a Select to get current ID's
     *
     */
    public function testFilterById()
    {
        // find by single id
        $book = PropelQuery::from('Book b')
            ->where('b.Title like ?', 'Don%')
            ->orderBy('b.ISBN', 'desc')
            ->findOne();

        $c = BookQuery::create()->filterById($book->getId());

        $book2 = $c->findOne();

        $this->assertTrue($book2 instanceof Book);
        $this->assertEquals('Don Juan', $book2->getTitle());

        //find range
        $booksAll = PropelQuery::from('Book b')
            ->orderBy('b.ID', 'asc')
            ->find();

        $booksIn = BookQuery::create()
            ->filterById(array($booksAll[1]->getId(), $booksAll[2]->getId()))
            ->find();

        $this->assertTrue($booksIn[0] == $booksAll[1]);
        $this->assertTrue($booksIn[1] == $booksAll[2]);

        // filter by min value with greater equal
        $booksIn = null;

        $booksIn = BookQuery::create()
                ->filterById(
                     array('min' => $booksAll[2]->getId()))
                ->find();

        $this->assertTrue($booksIn[1] == $booksAll[3]);

        // filter by max value with less equal
        $booksIn = null;

        $booksIn = BookQuery::create()
            ->filterById(
                array('max' => $booksAll[1]->getId()) )
            ->find();

        $this->assertTrue($booksIn[1] == $booksAll[1]);

        // check backwards compatibility:
        // SELECT  FROM `book` WHERE book.id IN (:p1,:p2)
        // must be the same as
        // SELECT  FROM `book` WHERE (book.id>=:p1 AND book.id<=:p2)

        $minMax = BookQuery::create()
            ->filterById(
                array('min' => $booksAll[1]->getId(),
                      'max' => $booksAll[2]->getId())
                )
            ->find();

        $In = BookQuery::create()
            ->filterById(
                array($booksAll[1]->getId(),
                      $booksAll[2]->getId())
                )
            ->find();

        $this->assertTrue($minMax[0] === $In[0]);
        $this->assertTrue(count($minMax->getData()) === count($In->getData()));
    }

    public function testInstancePool()
    {
        $object = new Table6();
        $object->setTitle('test');
        $object->save();
        $key = $object->getId();

        $this->assertSame($object, Table6Peer::getInstanceFromPool($key));
        Table6Peer::removeInstanceFromPool($object);
        $this->assertNull(Table6Peer::getInstanceFromPool($key));

        $object = Table6Query::create()->findPk($key);
        $this->assertSame($object, Table6Peer::getInstanceFromPool($key));
    }
}

class myBookQuery extends BookQuery
{
}
