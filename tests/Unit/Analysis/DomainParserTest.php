<?php

namespace Tests\Unit\Analysis;

use App\Services\Analysis\Link\DomainParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DomainParserTest extends TestCase
{
    private DomainParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DomainParser;
    }

    #[Test]
    public function it_extracts_links_from_a_forwarded_message(): void
    {
        $message = "Paket Anda tertahan. Cek di https://kurir-cek.xyz/resi?id=123, atau bit.ly/3abc.\nFile: Undangan.apk, kontak admin@contoh.com";

        $this->assertSame(['https://kurir-cek.xyz/resi?id=123', 'bit.ly/3abc'], $this->parser->extractUrls($message));
    }

    #[Test]
    public function it_ignores_file_names_amounts_and_emails(): void
    {
        $this->assertSame([], $this->parser->extractUrls('Undangan_Pernikahan.apk Rp1.500.000 cs@bank.co.id v2.1.0'));
    }

    #[Test]
    public function it_normalizes_hosts(): void
    {
        $this->assertSame('bca-co.id', $this->parser->host('HTTPS://WWW.BCA-CO.ID/login'));
        $this->assertSame('login.bca-co.id', $this->parser->host('login.bca-co.id.'));
        $this->assertSame('xn--tkopedia-nbh.com', $this->parser->host('tоkopedia.com'));
        $this->assertNull($this->parser->host('http://192.168.1.1/admin'));
        $this->assertNull($this->parser->host('http://localhost:8080'));
    }

    #[Test]
    public function it_finds_the_registrable_domain(): void
    {
        $this->assertSame('bca-co.id', $this->parser->registrableDomain('login.bca-co.id'));
        $this->assertSame('pajak.go.id', $this->parser->registrableDomain('djponline.pajak.go.id'));
        $this->assertSame('situs.my.id', $this->parser->registrableDomain('a.b.situs.my.id'));
        $this->assertSame('xyz.com', $this->parser->registrableDomain('bca.co.id.xyz.com'));
        $this->assertSame(['bca', 'co', 'id'], $this->parser->subdomainLabels('bca.co.id.xyz.com'));
    }

    #[Test]
    public function it_normalizes_lookalike_characters(): void
    {
        $this->assertContains('shopee', $this->parser->normalizedVariants('sh0pee'));
        $this->assertContains('bankmandiri', $this->parser->normalizedVariants('bankrnandiri'));
        $this->assertContains('klikbca', $this->parser->normalizedVariants('k1ikbca'));
        $this->assertContains('tokopedia', $this->parser->normalizedVariants('xn--tkopedia-nbh'));
    }
}
