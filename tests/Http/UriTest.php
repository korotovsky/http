<?php
namespace Icicle\Tests\Http;

use Icicle\Http\Uri;
use Icicle\Tests\TestCase;

class UriTest extends TestCase
{
    public function testConstructor()
    {
        $uri = new Uri('https://username:password@example.com:8443/async/http?foo=value1&bar=value2#fragment-value');
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('username:password', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8443, $uri->getPort());
        $this->assertSame('username:password@example.com:8443', $uri->getAuthority());
        $this->assertSame('/async/http', $uri->getPath());
        $this->assertSame('bar=value2&foo=value1', $uri->getQuery());
        $this->assertSame('value1', $uri->getQueryValue('foo'));
        $this->assertSame('value2', $uri->getQueryValue('bar'));
        $this->assertSame('fragment-value', $uri->getFragment());
    }

    /**
     * @depends testConstructor
     */
    public function testToString()
    {
        $uri = 'https://username:password@example.com:8443/async/http?name=value#fragment';
        $this->assertSame($uri, (string) new Uri($uri));
    }

    /**
     * @depends testConstructor
     */
    public function testWithScheme()
    {
        $uri = new Uri('https://username:password@example.com/async/http?name=value#fragment');

        $new = $uri->withScheme('http');

        $this->assertNotSame($uri, $new);
        $this->assertSame('http', $new->getScheme());
        $this->assertSame('http://username:password@example.com/async/http?name=value#fragment', (string) $new);

        $new = $uri->withScheme(null);

        $this->assertSame('', $new->getScheme());
        $this->assertSame('username:password@example.com/async/http?name=value#fragment', (string) $new);
    }

    /**
     * @depends testConstructor
     * @expectedException \Icicle\Http\Exception\InvalidArgumentException
     */
    public function testWithInvalidScheme()
    {
        new Uri('ftp://example.com/path');
    }

    /**
     * @return  array
     */
    public function getValidPorts()
    {
        return [
            ['http://example.com', 80, 80],
            ['https://example.com', 443, 443],
            ['https://example.com', 8080, 8080],
            ['http://example.com:8080', 80, 80],
            ['https://example.com:8080', 80, 80],
            ['https://example.com', 8443, 8443],
            ['http://example.com:8080', null, 80],
            ['https://example.com:8443', null, 443],
            ['https://example.com', '8080', 8080],
            ['example.com', 80, 80],
            ['example.com:80', null, null],
        ];
    }

    /**
     * @depends testConstructor
     * @dataProvider getValidPorts
     */
    public function testWithPort($uri, $port, $expected)
    {
        $uri = new Uri($uri);

        $new = $uri->withPort($port);

        $this->assertNotSame($uri, $new);
        $this->assertSame($expected, $new->getPort());
    }

    /**
     * @return  array
     */
    public function getInvalidPorts()
    {
        return [
            [-1],
            [0xfffff],
        ];
    }

    /**
     * @depends testConstructor
     * @dataProvider getInvalidPorts
     * @expectedException \Icicle\Http\Exception\InvalidArgumentException
     */
    public function testWithInvalidPort($port)
    {
        $uri = new Uri('http://example.com');
        $uri->withPort($port);
    }

    /**
     * @depends testConstructor
     */
    public function testWithUserInfo()
    {
        $uri = new Uri('https://example.com:8443');

        $new = $uri->withUserInfo('username', 'password');

        $this->assertNotSame($uri, $new);
        $this->assertSame('username:password', $new->getUserInfo());
        $this->assertSame('https://username:password@example.com:8443/', (string) $new);

        $new = $uri->withUserInfo('username');

        $this->assertNotSame($uri, $new);
        $this->assertSame('username', $new->getUserInfo());
        $this->assertSame('https://username@example.com:8443/', (string) $new);

        $new = $uri->withUserInfo('user name', 'påsswørd');

        $this->assertNotSame($uri, $new);
        $this->assertSame('user%20name:p%C3%A5ssw%C3%B8rd', $new->getUserInfo());
        $this->assertSame('https://user%20name:p%C3%A5ssw%C3%B8rd@example.com:8443/', (string) $new);
    }

    /**
     * @depends testConstructor
     */
    public function testWithHost()
    {
        $uri = new Uri('https://username:password@example.com:443/path');

        $new = $uri->withHost('example.net');

        $this->assertNotSame($uri, $new);
        $this->assertSame('example.net', $new->getHost());
        $this->assertSame('https://username:password@example.net/path', (string) $new);

        $new = $uri->withHost(null);

        $this->assertNotSame($uri, $new);
        $this->assertSame('', $new->getHost());
        $this->assertSame('/path', (string) $new);
    }

    /**
     * @return  array
     */
    public function getAuthorities()
    {
        return [
            ['https://example.com:443', 'example.com'],
            ['http://example.com:8080', 'example.com:8080'],
            ['http://username@example.com:80', 'username@example.com'],
            ['https://username:password@example.com:8443', 'username:password@example.com:8443'],
            ['https://username:password@example.com', 'username:password@example.com'],
            ['/no/authority', ''],
            ['example.com:80', 'example.com:80'],
            ['http://usérnäme:passwörd@example.com', 'us%C3%A9rn%C3%A4me:passw%C3%B6rd@example.com'],
        ];
    }

    /**
     * @dataProvider getAuthorities
     */
    public function testGetAuthority($uri, $expected)
    {
        $uri = new Uri($uri);
        $this->assertSame($expected, $uri->getAuthority());
    }

    /**
     * @depends testConstructor
     */
    public function testWithQuery()
    {
        $uri = new Uri('http://example.com/path?foo=bar');

        $new = $uri->withQuery('?key2=value2&key1=value1');

        $this->assertNotSame($uri, $new);
        $this->assertSame('key1=value1&key2=value2', $new->getQuery());
        $this->assertSame('value1', $new->getQueryValue('key1'));
        $this->assertSame('value2', $new->getQueryValue('key2'));
        $this->assertSame('http://example.com/path?key1=value1&key2=value2', (string) $new);

        $new = $uri->withQuery('test1&test2=value');

        $this->assertNotSame($uri, $new);
        $this->assertSame('test1&test2=value', $new->getQuery());
        $this->assertSame('', $new->getQueryValue('test1'));
        $this->assertSame('value', $new->getQueryValue('test2'));
        $this->assertSame('http://example.com/path?test1&test2=value', (string) $new);
    }

    /**
     * @depends testConstructor
     */
    public function testWithQueryValue()
    {
        $uri = new Uri('http://example.com/path?foo=bar');

        $new = $uri->withQueryValue('name', 'valüe');

        $this->assertNotSame($uri, $new);
        $this->assertSame('foo=bar&name=val%C3%BCe', $new->getQuery());
        $this->assertSame('http://example.com/path?foo=bar&name=val%C3%BCe', (string) $new);

        $new = $uri->withQueryValue('tést', null);

        $this->assertNotSame($uri, $new);
        $this->assertSame('foo=bar&t%C3%A9st', $new->getQuery());
        $this->assertSame('http://example.com/path?foo=bar&t%C3%A9st', (string) $new);

        $new = $uri->withQueryValue('foo', 'foo=bar');

        $this->assertNotSame($uri, $new);
        $this->assertSame('foo=foo=bar', $new->getQuery());
        $this->assertSame('http://example.com/path?foo=foo=bar', (string) $new);
    }

    /**
     * @depends testConstructor
     */
    public function testWithoutQueryValue()
    {
        $uri = new Uri('http://example.com/path?key1=value1&key2=value2&key3');

        $new = $uri->withoutQueryValue('key2');

        $this->assertNotSame($uri, $new);
        $this->assertSame('key1=value1&key3', $new->getQuery());
        $this->assertSame(null, $new->getQueryValue('key2'));
        $this->assertSame('http://example.com/path?key1=value1&key3', (string) $new);

        $new = $uri->withoutQueryValue('key1');

        $this->assertNotSame($uri, $new);
        $this->assertSame('key2=value2&key3', $new->getQuery());
        $this->assertSame(null, $new->getQueryValue('key1'));
        $this->assertSame('http://example.com/path?key2=value2&key3', (string) $new);

        $new = $uri->withoutQueryValue('key1');
        $new = $new->withoutQueryValue('key2');
        $new = $new->withoutQueryValue('key3');

        $this->assertNotSame($uri, $new);
        $this->assertSame('', $new->getQuery());
        $this->assertSame('http://example.com/path', (string) $new);
    }

    /**
     * @return array
     */
    public function getPaths()
    {
        return [
            ['/', '/'],
            ['', '/'],
            [null, '/'],
            ['path/to/file', '/path/to/file'],
            ['path with spaces', '/path%20with%20spaces'],
            ['påth/wïth/spécial/chârs', '/p%C3%A5th/w%C3%AFth/sp%C3%A9cial/ch%C3%A2rs'],
            ['/p%C3%A5th/w%C3%AFth/enc%C3%B8ded/ch%C3%A2rs', '/p%C3%A5th/w%C3%AFth/enc%C3%B8ded/ch%C3%A2rs'],
        ];
    }

    /**
     * @dataProvider getPaths
     */
    public function testWithPath($path, $expected)
    {
        $uri = new Uri('http://example.com/original/path');

        $new = $uri->withPath($path);

        $this->assertNotSame($uri, $new);
        $this->assertSame($expected, $new->getPath());
        $this->assertSame(sprintf('http://example.com%s', $expected), (string) $new);
    }

    /**
     * @return array
     */
    public function getFragments()
    {
        return [
            ['#', ''],
            ['', ''],
            [null, ''],
            ['new-fragment', 'new-fragment'],
            ['#with-hash', 'with-hash'],
            ['wïth/spécial/chârs', 'w%C3%AFth/sp%C3%A9cial/ch%C3%A2rs'],
            ['w%C3%AFth/enc%C3%B8ded/ch%C3%A2rs', 'w%C3%AFth/enc%C3%B8ded/ch%C3%A2rs'],
        ];
    }

    /**
     * @dataProvider getFragments
     */
    public function testWithFragment($path, $expected)
    {
        $uri = new Uri('http://example.com/path#original-fragment');

        $new = $uri->withFragment($path);

        $this->assertNotSame($uri, $new);
        $this->assertSame($expected, $new->getFragment());
    }
}
