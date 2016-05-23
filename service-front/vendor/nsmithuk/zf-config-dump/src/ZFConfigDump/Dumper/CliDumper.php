<?php
namespace ZFConfigDump\Dumper;

use Symfony\Component\VarDumper\Dumper\CliDumper as SymfonyCliDumper;

class CliDumper extends SymfonyCliDumper {

    /**
     * {@inheritdoc}
     */
    public function __construct($output = null, $charset = null)
    {

        $this->setStyles([
            'default' => '38;5;208',    // formatting (quote marks, =>, etc.)
            'num' => '1;38;5;38',       // Number value
            'const' => '1;38;5;88',     // Bool values
            'str' => '1;38;5;68',       // String values
            'note' => '38;5;126',        // eg. array:4
            'ref' => '38;5;247',        // Object refs
            'public' => '',
            'protected' => '',
            'private' => '',
            'meta' => '38;5;170',       // ?
            'key' => '38;5;35',         // String array keys
            'index' => '38;5;35',       // Numeric array key
        ]);

        parent::__construct($output, $charset);

    }

}
