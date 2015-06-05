<?php

namespace Debril\RssAtomBundle\Protocol;

use Debril\RssAtomBundle\Driver\FileDriver;
use Debril\RssAtomBundle\Protocol\Parser\Factory;
use Debril\RssAtomBundle\Protocol\Filter\ModifiedSince;
use Debril\RssAtomBundle\Driver\HttpDriverResponse;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-01-27 at 00:18:14.
 */
class FeedReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FeedReader
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new FeedReader(new FileDriver(), new Factory());
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::__construct
     */
    public function testConstruct()
    {
        $reader = new FeedReader(new FileDriver(), new Factory());

        $this->assertAttributeInstanceOf('\Debril\RssAtomBundle\Driver\FileDriver', 'driver', $reader);
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::addParser
     */
    public function testAddParser()
    {
        $parser = new Parser\AtomParser();
        $this->object->addParser($parser);

        $this->assertAttributeEquals(array($parser), 'parsers', $this->object);
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::getDriver
     *
     * @todo   Implement testGetDriver().
     */
    public function testGetDriver()
    {
        $this->assertInstanceOf(
            '\Debril\RssAtomBundle\Driver\HttpDriverInterface',
            $this->object->getDriver()
        );
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::getFeedContent
     * @expectedException \Debril\RssAtomBundle\Exception\ParserException
     */
    public function testGetFeedContentException()
    {
        $url = dirname(__FILE__).'/../../Resources/sample-rss.xml';

        $this->object->getFeedContent($url, new \DateTime());
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::readFeed
     * @dataProvider getRssInputs
     */
    public function testReadFeed($url, \DateTime $date)
    {
        $feed = $this->object
            ->addParser(new Parser\RssParser())
            ->readFeed($url, new Parser\FeedContent(), $date);

        $this->assertInstanceOf('\Debril\RssAtomBundle\Protocol\FeedInInterface', $feed);
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::getFeedContent
     * @dataProvider getRssInputs
     */
    public function testGetRssFeedContent($url, \DateTime $date)
    {
        $this->object->addParser(new Parser\RssParser());
        $this->validateFeed($this->object->getFeedContent($url, $date));
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::getFeedContent
     */
    public function testGetAtomFeedContent()
    {
        $url = dirname(__FILE__).'/../../Resources/sample-atom.xml';
        $this->object->addParser(new Parser\AtomParser());
        $date = \DateTime::createFromFormat('Y-m-d', '2002-10-10');

        $this->validateFeed($this->object->getFeedContent($url, $date));
    }

    /**
     * @param FeedInInterface $feed
     */
    protected function validateFeed(FeedInInterface $feed)
    {
        $this->assertInstanceOf('\Debril\RssAtomBundle\Protocol\FeedInInterface', $feed);

        $item = current($feed->getItems());
        $this->assertInstanceOf('\Debril\RssAtomBundle\Protocol\ItemInInterface', $item);

        $this->assertNotNull($item->getPublicId());
        $this->assertNotNull($item->getLink());
        $this->assertNotNull($item->getTitle());
        $this->assertNotNull($item->getDescription());
        $this->assertInstanceOf('\DateTime', $item->getUpdated());
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::getResponse
     */
    public function testGetResponse()
    {
        $url = dirname(__FILE__).'/../../Resources/sample-atom.xml';
        $this->object->addParser(new Parser\AtomParser());
        $response = $this->object->getResponse($url, new \DateTime());

        $this->assertInstanceOf('Debril\RssAtomBundle\Driver\HttpDriverResponse', $response);
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::parseBody
     */
    public function testAdditionalNamespacedElements()
    {
        $url = dirname(__FILE__).'/../../Resources/sample-atom-namespaced.xml';
        $this->object->addParser(new Parser\AtomParser());
        $response = $this->object->getResponse($url, new \DateTime());
        $feed = $this->object->parseBody($response, new Parser\FeedContent());
        $items = $feed->getItems();
        $additional = $items[0]->getAdditional();
        $this->assertEquals('http://original-link.com/item.html', $additional['feedburner']->origLink);
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::parseBody
     */
    public function testRssAdditionalNamespacedElements()
    {
        $url = dirname(__FILE__).'/../../Resources/sample-rss-media.xml';
        $this->object->addParser(new Parser\RssParser());
        $response = $this->object->getResponse($url, new \DateTime());
        $feed = $this->object->parseBody($response, new Parser\FeedContent());
        $items = $feed->getItems();
        $additional = $items[0]->getAdditional();
        $additionalAttributes = $additional['media']->thumbnail->attributes();
        $this->assertEquals('http://media-server.com/image.jpg', $additionalAttributes['url']);
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::parseBody
     */
    public function testParseBody()
    {
        $url = dirname(__FILE__).'/../../Resources/sample-rss.xml';
        $this->object->addParser(new Parser\RssParser());

        $date = new \DateTime();
        $response = $this->object->getResponse($url, $date);

        $filters = array(new ModifiedSince($date));
        $feed = $this->object->parseBody($response, new Parser\FeedContent(), $filters);

        $this->assertInstanceOf('\Debril\RssAtomBundle\Protocol\FeedInInterface', $feed);
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::getAccurateParser
     */
    public function testGetAccurateParser()
    {
        $this->object->addParser(new Parser\RssParser());
        $this->object->addParser(new Parser\RdfParser());
        $this->object->addParser(new Parser\AtomParser());

        $url = dirname(__FILE__).'/../../Resources/sample-rdf.xml';

        $rdfBody = $this->object->getResponse($url, new \DateTime())->getBody();

        $this->assertInstanceOf(
            'Debril\RssAtomBundle\Protocol\Parser\RdfParser',
            $this->object->getAccurateParser(new \SimpleXMLElement($rdfBody))
        );

        $url = dirname(__FILE__).'/../../Resources/sample-rss.xml';

        $rssBody = $this->object->getResponse($url, new \DateTime())->getBody();

        $this->assertInstanceOf(
            'Debril\RssAtomBundle\Protocol\Parser\RssParser',
            $this->object->getAccurateParser(new \SimpleXMLElement($rssBody))
        );

        $url = dirname(__FILE__).'/../../Resources/sample-atom.xml';

        $atomBody = $this->object->getResponse($url, new \DateTime())->getBody();

        $this->assertInstanceOf(
            "Debril\RssAtomBundle\Protocol\Parser\AtomParser",
            $this->object->getAccurateParser(new \SimpleXMLElement($atomBody))
        );
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::getAccurateParser
     * @expectedException \Debril\RssAtomBundle\Exception\ParserException
     */
    public function testGetAccurateParserException()
    {
        $url = dirname(__FILE__).'/../../Resources/sample-rss.xml';
        $rssBody = $this->object->getResponse($url, new \DateTime())->getBody();
        $this->object->getAccurateParser(new \SimpleXMLElement($rssBody));
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::parseBody
     * @expectedException \Debril\RssAtomBundle\Exception\FeedException\FeedNotModifiedException
     */
    public function testParseBody304()
    {
        $reader = new FeedReader($this->getMockDriver(304), new Factory());

        $reader->getFeedContent('http://afakeurl', new \DateTime());
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::parseBody
     * @expectedException \Debril\RssAtomBundle\Exception\FeedException\FeedNotFoundException
     */
    public function testParseBody404()
    {
        $reader = new FeedReader($this->getMockDriver(404), new Factory());

        $reader->getFeedContent('http://afakeurl', new \DateTime());
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::parseBody
     * @expectedException \Debril\RssAtomBundle\Exception\FeedException\FeedServerErrorException
     */
    public function testParseBody500()
    {
        $reader = new FeedReader($this->getMockDriver(500), new Factory());

        $reader->getFeedContent('http://afakeurl', new \DateTime());
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::parseBody
     * @expectedException \Debril\RssAtomBundle\Exception\FeedException\FeedForbiddenException
     */
    public function testParseBody403()
    {
        $reader = new FeedReader($this->getMockDriver(403), new Factory());

        $reader->getFeedContent('http://afakeurl', new \DateTime());
    }

    /**
     * @covers Debril\RssAtomBundle\Protocol\FeedReader::parseBody
     * @expectedException \Debril\RssAtomBundle\Exception\FeedException\FeedCannotBeReadException
     */
    public function testParseBodyUnknownError()
    {
        $reader = new FeedReader($this->getMockDriver(666), new Factory());

        $reader->getFeedContent('http://afakeurl', new \DateTime());
    }

    /**
     * @param $responseHttpCode
     *
     * @return \Debril\RssAtomBundle\Driver\HttpCurlDriver a mocked instance
     */
    public function getMockDriver($responseHttpCode)
    {
        $mock = $this->getMock('\Debril\RssAtomBundle\Driver\HttpCurlDriver');

        $response = new HttpDriverResponse();
        $response->setHttpCode($responseHttpCode);

        $mock->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($response));

        return $mock;
    }

    /**
     * @return array
     */
    public function getRssInputs()
    {
        return array(
            array(
                dirname(__FILE__).'/../../Resources/sample-rss.xml',
                \DateTime::createFromFormat('Y-m-d', '2005-10-10'),
            ),
        );
    }
}
