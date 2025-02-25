<?php

namespace Eightfold\CommonMarkAbbreviations\Tests;

use PHPUnit\Framework\TestCase;

use League\CommonMark\Environment;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;

use Eightfold\Shoop\Shoop;

use Eightfold\CommonMarkAbbreviations\Abbreviation;
use Eightfold\CommonMarkAbbreviations\AbbreviationExtension;

class AbbreviationAttributesTest extends TestCase
{
    public function testParser()
    {
        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension(new AbbreviationExtension());
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addEventListener(DocumentParsedEvent::class, function (
            DocumentParsedEvent $event
        ) {
            $document = $event->getDocument();
            $walker = $document->walker();
            while ($event = $walker->next()) {
                $node = $event->getNode();

                // Ignore any nodes that aren't Abbreviation nodes, and only act
                // when we first encounter/enter an Abbreviation node.
                if (!($node instanceof Abbreviation) || !$event->isEntering()) {
                    continue;
                }

                // Add a test attribute. It's also possible to alter the
                // existing attributes here.
                $node->data['attributes']['data-event-attribute'] = 'hello';
            }
        });
        $converter = new CommonMarkConverter([
            "external_link" => ["open_in_new_window" => true]
        ], $environment);

        $path = Shoop::this(__DIR__)->append("/short-doc-attributes.md");
        $markdown = file_get_contents($path);
        $expected = '<p><abbr title="United States Web Design System" data-inline-attribute="hello" data-event-attribute="hello">USWDS</abbr></p>'."\n".'<p><a rel="noopener noreferrer" target="_blank" href="https://8fold.pro">External link check</a></p>'."\n";
        $actual = $converter->convertToHtml($markdown);
        $this->assertEquals($expected, $actual);

        $path = Shoop::this(__DIR__)->divide("/")
            ->dropLast()->append(["readme.html"])->asString("/");
        $expected = file_get_contents($path);

        $path = Shoop::this(__DIR__)->divide("/")
            ->dropLast()->append(["README.md"])->asString("/");
        $markdown = file_get_contents($path);

        $actual = $converter->convertToHtml($markdown);
        $this->assertEquals($expected, $actual);
    }
}
