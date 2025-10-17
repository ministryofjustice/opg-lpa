<?php

declare(strict_types=1);

namespace MakeShared\Psalm;

use PhpParser\Node\Expr\Error as PhpParserError;
use Psalm\CodeLocation;
use Psalm\Issue\PluginIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;
use json_decode;
use json_encode;

/**
 * Example configuration in psalm.xml:
 *
 * <plugins>
 *     <pluginClass class="\MakeShared\Psalm\SensitiveParameterAnnotationHook">
 *         <patterns>
 *             <pattern>password</pattern>
 *             <pattern>email</pattern>
 *             <pattern>username</pattern>
 *             <pattern>token</pattern>
 *             <pattern>address</pattern>
 *             <pattern>secret</pattern>
 *             <pattern>cookie</pattern>
 *         </patterns>
 *         <ignore>
 *             <name>emailTemplatesConfig</name>
 *             <name>addressLookup</name>
 *         </ignore>
 *     </pluginClass>
 * </plugins>
 */
class SensitiveParameterAnnotationHook implements PluginEntryPointInterface, AfterFunctionLikeAnalysisInterface
{
    // parameter name patterns which might indicate they are sensitive;
    // add more by setting <patterns> in config
    private static array $patterns = ['password', 'secret', 'token', 'email'];

    // parameters which look sensitive but aren't and should be ignored
    private static array $ignore = [];

    public function __invoke(RegistrationInterface $psalm, ?SimpleXMLElement $config = null): void
    {
        $psalm->registerHooksFromClass(self::class);

        $configArr = json_decode(json_encode($config), true);

        if (isset($configArr['patterns'])) {
            self::$patterns = array_merge($configArr['patterns']['pattern'], self::$patterns);
        }

        self::$patterns = array_map(
            function ($pattern) {
                return '/' . $pattern . '/i';
            },
            self::$patterns
        );

        if (isset($configArr['ignore'])) {
            self::$ignore = $configArr['ignore']['name'];
        }
    }

    public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool
    {
        $stmt = $event->getStmt();
        $params = $stmt->getParams();
        $statementsSource = $event->getStatementsSource();

        foreach ($params as $param) {
            if ($param->var instanceof PhpParserError) {
                continue;
            }

            $paramName = $param->var->name;

            // is the parameter potentially sensitive?
            $sensitive = false;
            foreach (self::$patterns as $sensitiveParameterPattern) {
                if (
                    preg_match($sensitiveParameterPattern, $paramName) === 1
                    && !in_array($paramName, self::$ignore)
                ) {
                    $sensitive = true;
                    break;
                }
            }

            if (!$sensitive) {
                continue;
            }

            // does the parameter already have the SensitiveParameter annotation?
            $hasAnnotation = false;
            foreach ($param->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attr) {
                    if (in_array('SensitiveParameter', $attr->name->getParts())) {
                        $hasAnnotation = true;
                        break;
                    }
                }
            }

            if ($hasAnnotation) {
                continue;
            }

            // add a warning that the SensitiveParameter annotation is missing
            IssueBuffer::maybeAdd(
                new SensitiveParameterAnnotation(
                    'Potential sensitive parameter(s) without annotation(s): "' .
                        $paramName . '"; ' .
                        ' add `#[\SensitiveParameter] ` before parameter(s)',
                    new CodeLocation($statementsSource, $stmt),
                ),
                $statementsSource->getSuppressedIssues(),
            );
        }

        return null;
    }
}

class SensitiveParameterAnnotation extends PluginIssue
{
}
