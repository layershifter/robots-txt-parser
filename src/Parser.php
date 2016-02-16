<?php

namespace Layershifter\Robots;

/**
 * Class for parsing robots.txt files
 *
 * @author Eugene Yurkevich (yurkevich@vicman.net)
 *
 *
 * Some useful links and materials:
 * @link   https://developers.google.com/webmasters/control-crawl-index/docs/robots_txt
 * @link   https://help.yandex.com/webmaster/controlling-robot/robots-txt.xml
 */

class Parser
{

    /**
     * Default encoding of filex
     */
    const DEFAULT_ENCODING = 'UTF-8';

    /**
     *
     */
    const DIRECTIVE_ALLOW = 'allow';
    /**
     *
     */
    const DIRECTIVE_DISALLOW = 'disallow';
    /**
     *
     */
    const DIRECTIVE_HOST = 'host';
    /**
     *
     */
    const DIRECTIVE_SITEMAP = 'sitemap';
    /**
     *
     */
    const DIRECTIVE_USERAGENT = 'user-agent';
    /**
     *
     */
    const DIRECTIVE_CRAWL_DELAY = 'crawl-delay';
    /**
     *
     */
    const DIRECTIVE_CLEAN_PARAM = 'clean-param';

    // states
    /**
     *
     */
    const STATE_ZERO_POINT = 'zero-point';
    /**
     *
     */
    const STATE_READ_DIRECTIVE = 'read-directive';
    /**
     *
     */
    const STATE_SKIP_SPACE = 'skip-space';
    /**
     *
     */
    const STATE_SKIP_LINE = 'skip-line';
    /**
     *
     */
    const STATE_READ_VALUE = 'read-value';

    /**
     * @var string Current state
     */
    private $state = '';
    /**
     * @var string Content of loaded robots.txt
     */
    private $content = '';

    // rules set
    /**
     * @var array
     */
    private $rules = [];
    /**
     * @var string
     */
    private $currentWord = '';
    /**
     * @var string
     */
    private $currentChar = '';
    /**
     * @var int
     */
    private $charIndex = 0;
    /**
     * @var string
     */
    private $currentDirective = '';
    /**
     * @var string
     */
    private $userAgent = '*';

    /**
     * @param string $content  File content
     * @param string $encoding Encoding
     *
     * @return Parser
     */
    public function __construct($content, $encoding = self::DEFAULT_ENCODING)
    {
        // convert encoding

        $encoding = $encoding === '' ? $encoding : $this->detectEncoding($content);
        mb_internal_encoding($encoding);

        // set content

        $this->content = iconv($encoding, 'UTF-8//IGNORE', $content) . PHP_EOL;

        // set default state

        $this->state = self::STATE_ZERO_POINT;

        // parse rules - default state

        $this->prepareRules();
    }

    /**
     * @param string $content File content
     *
     * @return string
     */
    private function detectEncoding($content)
    {
        $encoding = mb_detect_encoding($content);

        return $encoding !== false ? $encoding : self::DEFAULT_ENCODING;
    }

    /**
     * @param null|string $userAgent Name of user agent
     *
     * @return array
     */
    public function getRules($userAgent = null)
    {
        if ($userAgent === null) {
            //return all rules
            return $this->rules;
        }

        if (array_key_exists($userAgent, $this->rules)) {
            return $this->rules[$userAgent];
        }

        return [];
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Comment signal (#)
     *
     * @return boolean
     */
    private function isSharp()
    {
        return $this->currentChar === '#';
    }

    /**
     * Sitemap directive signal
     *
     * @return boolean
     */
    private function isDirectiveSitemap()
    {
        return $this->currentDirective === self::DIRECTIVE_SITEMAP;
    }

    /**
     * Clean-param directive signal
     *
     * @return boolean
     */
    private function isDirectiveCleanParam()
    {
        return $this->currentDirective === self::DIRECTIVE_CLEAN_PARAM;
    }

    /**
     * Host directive signal
     *
     * @return boolean
     */
    private function isDirectiveHost()
    {
        return $this->currentDirective === self::DIRECTIVE_HOST;
    }

    /**
     * User-agent directive signal
     *
     * @return boolean
     */
    private function isDirectiveUserAgent()
    {
        return $this->currentDirective === self::DIRECTIVE_USERAGENT;
    }

    /**
     * Crawl-Delay directive signal
     *
     * @return boolean
     */
    private function isDirectiveCrawlDelay()
    {
        return $this->currentDirective === self::DIRECTIVE_CRAWL_DELAY;
    }

    /**
     * Key : value pair separator signal
     *
     * @return boolean
     */
    private function isLineSeparator()
    {
        return $this->currentChar === ':';
    }

    /**
     * Move to new line signal
     *
     * @return boolean
     */
    private function isNewLine()
    {
        $asciiCode = ord($this->currentChar);

        return
            $this->currentChar === "\n"
            || $this->currentWord === "\r\n"
            || $this->currentWord === "\n\r"
            || $asciiCode === 13
            || $asciiCode === 10;
    }

    /**
     * "Space" signal
     *
     * @return boolean
     */
    private function isSpace()
    {
        return $this->currentChar === "\s";
    }

    /**
     * Change state
     *
     * @param string $stateTo - state that should be set
     *
     * @return void
     */
    private function switchState($stateTo = self::STATE_SKIP_LINE)
    {
        $this->state = $stateTo;
    }

    /**
     * Parse rules
     *
     * @return void
     */
    public function prepareRules()
    {
        $contentLength = mb_strlen($this->content);
        while ($this->charIndex <= $contentLength) {
            $this->step();
        }

        foreach ($this->rules as $userAgent => $directive) {
            foreach ($directive as $directiveName => $directiveValue) {
                if (is_array($directiveValue)) {
                    $this->rules[$userAgent][$directiveName] = array_values(array_unique($directiveValue));
                }
            }
        }
    }

    /**
     * Check if we should switch
     *
     * @return bool
     */
    private function shouldSwitchToZeroPoint()
    {
        return in_array($this->currentWord, array(
            self::DIRECTIVE_ALLOW,
            self::DIRECTIVE_DISALLOW,
            self::DIRECTIVE_HOST,
            self::DIRECTIVE_USERAGENT,
            self::DIRECTIVE_SITEMAP,
            self::DIRECTIVE_CRAWL_DELAY,
            self::DIRECTIVE_CLEAN_PARAM,
        ), true);
    }

    /**
     * Process state ZERO_POINT
     *
     * @return Parser
     */
    private function zeroPoint()
    {
        if ($this->shouldSwitchToZeroPoint()) {
            $this->switchState(self::STATE_READ_DIRECTIVE);
        } // unknown directive - skip it
        elseif ($this->isNewLine()) {
            $this->currentWord = '';
            $this->increment();
        } else {
            $this->increment();
        }
        return $this;
    }

    /**
     * Read directive
     *
     * @return Parser
     */
    private function readDirective()
    {
        $this->currentDirective = mb_strtolower(trim($this->currentWord));

        $this->increment();

        if ($this->isLineSeparator()) {
            $this->currentWord = '';
            $this->switchState(self::STATE_READ_VALUE);
        } else {
            if ($this->isSpace()) {
                $this->switchState(self::STATE_SKIP_SPACE);
            }
            if ($this->isSharp()) {
                $this->switchState(self::STATE_SKIP_LINE);
            }
        }
        return $this;
    }

    /**
     * Skip space
     *
     * @return Parser
     */
    private function skipSpace()
    {
        $this->charIndex++;
        $this->currentWord = mb_substr($this->currentWord, -1);
        return $this;
    }

    /**
     * Skip line
     *
     * @return Parser
     */
    private function skipLine()
    {
        $this->charIndex++;
        $this->switchState(self::STATE_ZERO_POINT);
        return $this;
    }

    /**
     * Read value
     *
     * @return Parser
     */
    private function readValue()
    {
        if ($this->isNewLine()) {
            $this->assignValueToDirective();
        } elseif ($this->isSharp()) {
            $this->currentWord = mb_substr($this->currentWord, 0, -1);
            $this->assignValueToDirective();
        } else {
            $this->increment();
        }
        return $this;
    }

    /**
     * @return void
     */
    private function assignValueToDirective()
    {
        if ($this->isDirectiveUserAgent()) {
            if (empty($this->rules[$this->currentWord])) {
                $this->rules[$this->currentWord] = array();
            }
            $this->userAgent = $this->currentWord;
        } elseif ($this->isDirectiveCrawlDelay()) {
            $this->rules[$this->userAgent][$this->currentDirective] = (double)$this->currentWord;
        } elseif ($this->isDirectiveSitemap()) {
            $this->rules[$this->userAgent][$this->currentDirective][] = $this->currentWord;
        } elseif ($this->isDirectiveCleanParam()) {
            $this->rules[$this->userAgent][$this->currentDirective][] = trim($this->currentWord);
        } elseif ($this->isDirectiveHost()) {
            if (empty($this->rules['*'][$this->currentDirective])) {
                // save only first host directive value, assign to '*'
                $this->rules['*'][$this->currentDirective] = $this->currentWord;
            }
        } else {
            if (!empty($this->currentWord)) {
                $this->rules[$this->userAgent][$this->currentDirective][] = $this->currentWord;
            }
        }
        $this->currentWord = '';
        $this->currentDirective = '';
        $this->switchState(self::STATE_ZERO_POINT);
    }

    /**
     * Machine step
     *
     * @return void
     */
    private function step()
    {
        switch ($this->state) {
            case self::STATE_ZERO_POINT:
                $this->zeroPoint();
                break;

            case self::STATE_READ_DIRECTIVE:
                $this->readDirective();
                break;

            case self::STATE_SKIP_SPACE:
                $this->skipSpace();
                break;

            case self::STATE_SKIP_LINE:
                $this->skipLine();
                break;

            case self::STATE_READ_VALUE:
                $this->readValue();
                break;
        }
    }

    /**
     * Move to the following step
     *
     * @return void
     */
    private function increment()
    {
        $this->currentChar = mb_strtolower(mb_substr($this->content, $this->charIndex, 1));
        $this->currentWord .= $this->currentChar;
        if (!$this->isDirectiveCleanParam()) {
            $this->currentWord = trim($this->currentWord);
        }
        $this->charIndex++;
    }
}
