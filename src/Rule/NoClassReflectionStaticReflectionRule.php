<?php

declare(strict_types=1);

namespace Rector\PHPStanRules\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use Rector\PHPStanRules\TypeAnalyzer\AllowedAutoloadedTypeAnalyzer;
use Symplify\Astral\Naming\SimpleNameResolver;
use Symplify\PHPStanRules\Rules\AbstractSymplifyRule;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\PHPStanRules\Tests\Rule\NoClassReflectionStaticReflectionRule\NoClassReflectionStaticReflectionRuleTest
 */
final class NoClassReflectionStaticReflectionRule extends AbstractSymplifyRule
{
    /**
     * @var string
     */
    public const ERROR_MESSAGE = 'Instead of "new ClassReflection()" use ReflectionProvider service or "(new PHPStan\Reflection\ClassReflection(<desired_type>))" for static reflection to work';

    public function __construct(
        private SimpleNameResolver $simpleNameResolver,
        private AllowedAutoloadedTypeAnalyzer $allowedAutoloadedTypeAnalyzer
    ) {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [New_::class];
    }

    /**
     * @param New_ $node
     * @return string[]
     */
    public function process(Node $node, Scope $scope): array
    {
        if (count($node->args) !== 1) {
            return [];
        }

        $className = $this->simpleNameResolver->getName($node->class);
        if ($className !== 'ReflectionClass') {
            return [];
        }

        $argValue = $node->args[0]->value;
        $exprStaticType = $scope->getType($argValue);

        if ($this->allowedAutoloadedTypeAnalyzer->isAllowedType($exprStaticType)) {
            return [];
        }

        return [self::ERROR_MESSAGE];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(self::ERROR_MESSAGE, [
            new CodeSample(
                <<<'CODE_SAMPLE'
$classReflection = new ClassReflection($someType);
CODE_SAMPLE
            ,
                <<<'CODE_SAMPLE'
if ($this->reflectionProvider->hasClass($someType)) {
    $classReflection = $this->reflectionProvider->getClass($someType);
}
CODE_SAMPLE
            ),
        ]);
    }
}
