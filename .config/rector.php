<?php

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodeQuality\Rector\Assign\CombinedAssignRector;
use Rector\CodeQuality\Rector\BooleanAnd\RemoveUselessIsObjectCheckRector;
use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\CodeQuality\Rector\BooleanNot\ReplaceMultipleBooleanNotRector;
use Rector\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector;
use Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\ClassConstFetch\ConvertStaticPrivateConstantToSelfRector;
use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\Expression\TernaryFalseExpressionToIfRector;
use Rector\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachToInArrayRector;
use Rector\CodeQuality\Rector\Foreach_\SimplifyForeachToCoalescingRector;
use Rector\CodeQuality\Rector\FuncCall\ArrayMergeOfNonArraysToSimpleArrayRector;
use Rector\CodeQuality\Rector\FuncCall\BoolvalToTypeCastRector;
use Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\FuncCall\FloatvalToTypeCastRector;
use Rector\CodeQuality\Rector\FuncCall\InlineIsAInstanceOfRector;
use Rector\CodeQuality\Rector\FuncCall\IntvalToTypeCastRector;
use Rector\CodeQuality\Rector\FuncCall\SetTypeToCastRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyFuncGetArgsCountRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyInArrayValuesRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyStrposLowerRector;
use Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Rector\CodeQuality\Rector\FuncCall\StrvalToTypeCastRector;
use Rector\CodeQuality\Rector\FuncCall\UnwrapSprintfOneArgumentRector;
use Rector\CodeQuality\Rector\Identical\BooleanNotIdenticalToNotIdenticalRector;
use Rector\CodeQuality\Rector\Identical\GetClassToInstanceOfRector;
use Rector\CodeQuality\Rector\Identical\SimplifyArraySearchRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\Identical\SimplifyConditionsRector;
use Rector\CodeQuality\Rector\Identical\StrlenZeroToIdenticalEmptyStringRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\CompleteMissingIfElseBracketRector;
use Rector\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfNotNullReturnRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfNullableReturnRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector;
use Rector\CodeQuality\Rector\LogicalAnd\AndAssignsToSeparateLinesRector;
use Rector\CodeQuality\Rector\New_\NewStaticToNewSelfRector;
use Rector\CodeQuality\Rector\NotEqual\CommonNotEqualRector;
use Rector\CodeQuality\Rector\NullsafeMethodCall\CleanupUnneededNullsafeOperatorRector;
use Rector\CodeQuality\Rector\Switch_\SingularSwitchToIfRector;
use Rector\CodeQuality\Rector\Switch_\SwitchTrueToIfRector;
use Rector\CodeQuality\Rector\Ternary\ArrayKeyExistsTernaryThenValueToCoalescingRector;
use Rector\CodeQuality\Rector\Ternary\NumberCompareToMaxFuncCallRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\CodeQuality\Rector\Ternary\TernaryEmptyArrayArrayDimFetchToCoalesceRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\CodingStyle\Rector\ArrowFunction\StaticArrowFunctionRector;
use Rector\CodingStyle\Rector\Assign\SplitDoubleAssignRector;
use Rector\CodingStyle\Rector\ClassConst\RemoveFinalFromConstRector;
use Rector\CodingStyle\Rector\ClassConst\SplitGroupedClassConstantsRector;
use Rector\CodingStyle\Rector\ClassMethod\FuncGetArgsToVariadicParamRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\CodingStyle\Rector\Encapsed\WrapEncapsedVariableInCurlyBracesRector;
use Rector\CodingStyle\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\CodingStyle\Rector\FuncCall\CallUserFuncArrayToVariadicRector;
use Rector\CodingStyle\Rector\FuncCall\CallUserFuncToMethodCallRector;
use Rector\CodingStyle\Rector\FuncCall\ConsistentImplodeRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\FuncCall\VersionCompareFuncCallToConstantRector;
use Rector\CodingStyle\Rector\If_\NullableCompareToNullRector;
use Rector\CodingStyle\Rector\Plus\UseIncrementAssignRector;
use Rector\CodingStyle\Rector\Property\SplitGroupedPropertiesRector;
use Rector\CodingStyle\Rector\Stmt\RemoveUselessAliasInUseStatementRector;
use Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector;
use Rector\CodingStyle\Rector\Ternary\TernaryConditionVariableAssignmentRector;
use Rector\CodingStyle\Rector\Use_\SeparateMultiUseImportsRector;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Removing\Rector\ClassMethod\ArgumentRemoverRector;
use Rector\Renaming\Rector\ClassMethod\RenameAnnotationRector;
use Rector\Renaming\Rector\ConstFetch\RenameConstantRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

/**
 * @see https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        PHPUnitSetList::PHPUNIT_100,
    ]);

    // Run faster
    $rectorConfig->parallel();

    // The paths to refactor (can also be supplied with CLI arguments)
    $rectorConfig->paths([
        __DIR__ . '/../src/',
        // __DIR__ . '/tests/',
    ]);

    // Include Composer's autoload - required for global execution, remove if running locally
    $rectorConfig->autoloadPaths([
        __DIR__ . '/../vendor/autoload.php',
    ]);

    // Do you need to include constants, class aliases, or a custom autoloader?
    $rectorConfig->bootstrapFiles([
        realpath(getcwd()) . '/vendor/codeigniter4/framework/system/Test/bootstrap.php',
    ]);

    // Set the target version for refactoring
    $rectorConfig->phpVersion(PhpVersion::PHP_74);

    // Auto-import fully qualified class names
    $rectorConfig->importNames();

    // Are there files or rules you need to skip?
    $rectorConfig->skip([
        __DIR__ . '/../app/Views',
    ]);

    // auto import fully qualified class names
    $rectorConfig->importNames();

    // Early Return
    $rectorConfig->rule(ChangeIfElseValueAssignToEarlyReturnRector::class);
    $rectorConfig->rule(ChangeNestedForeachIfsToEarlyContinueRector::class);
    $rectorConfig->rule(ChangeNestedIfsToEarlyReturnRector::class);
    $rectorConfig->rule(PreparedValueToEarlyReturnRector::class);
    $rectorConfig->rule(RemoveAlwaysElseRector::class);
    $rectorConfig->rule(ReturnEarlyIfVariableRector::class);
    // InstanceOf
    $rectorConfig->rule(FlipNegatedTernaryInstanceofRector::class);
    // Code Quality
    $rectorConfig->rule(AbsolutizeRequireAndIncludePathRector::class);
    $rectorConfig->rule(AndAssignsToSeparateLinesRector::class);
    $rectorConfig->rule(ArrayKeyExistsTernaryThenValueToCoalescingRector::class);
    $rectorConfig->rule(ArrayMergeOfNonArraysToSimpleArrayRector::class);
    $rectorConfig->rule(BooleanNotIdenticalToNotIdenticalRector::class);
    $rectorConfig->rule(BoolvalToTypeCastRector::class);
    $rectorConfig->rule(CallableThisArrayToAnonymousFunctionRector::class);
    $rectorConfig->rule(ChangeArrayPushToArrayAssignRector::class);
    $rectorConfig->rule(CleanupUnneededNullsafeOperatorRector::class);
    $rectorConfig->rule(CombineIfRector::class);
    $rectorConfig->rule(CombinedAssignRector::class);
    $rectorConfig->rule(CommonNotEqualRector::class);
    $rectorConfig->rule(CompactToVariablesRector::class);
    $rectorConfig->rule(CompleteDynamicPropertiesRector::class);
    $rectorConfig->rule(CompleteMissingIfElseBracketRector::class);
    $rectorConfig->rule(ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class);
    $rectorConfig->rule(ConvertStaticPrivateConstantToSelfRector::class);
    $rectorConfig->rule(FloatvalToTypeCastRector::class);
    $rectorConfig->rule(ForRepeatedCountToOwnVariableRector::class);
    $rectorConfig->rule(ForeachToInArrayRector::class);
    $rectorConfig->rule(GetClassToInstanceOfRector::class);
    $rectorConfig->rule(InlineIfToExplicitIfRector::class);
    $rectorConfig->rule(InlineIsAInstanceOfRector::class);
    $rectorConfig->rule(IntvalToTypeCastRector::class);
    $rectorConfig->rule(JoinStringConcatRector::class);
    $rectorConfig->rule(NewStaticToNewSelfRector::class);
    $rectorConfig->rule(NumberCompareToMaxFuncCallRector::class);
    $rectorConfig->rule(RemoveUselessIsObjectCheckRector::class);
    $rectorConfig->rule(ReplaceMultipleBooleanNotRector::class);
    $rectorConfig->rule(SetTypeToCastRector::class);
    $rectorConfig->rule(ShortenElseIfRector::class);
    $rectorConfig->rule(SimplifyArraySearchRector::class);
    $rectorConfig->rule(SimplifyBoolIdenticalTrueRector::class);
    $rectorConfig->rule(SimplifyConditionsRector::class);
    $rectorConfig->rule(SimplifyDeMorganBinaryRector::class);
    $rectorConfig->rule(SimplifyEmptyArrayCheckRector::class);
    $rectorConfig->rule(SimplifyEmptyCheckOnEmptyArrayRector::class);
    $rectorConfig->rule(SimplifyForeachToCoalescingRector::class);
    $rectorConfig->rule(SimplifyFuncGetArgsCountRector::class);
    $rectorConfig->rule(SimplifyIfElseToTernaryRector::class);
    $rectorConfig->rule(SimplifyIfNotNullReturnRector::class);
    $rectorConfig->rule(SimplifyIfNullableReturnRector::class);
    $rectorConfig->rule(SimplifyIfReturnBoolRector::class);
    $rectorConfig->rule(SimplifyInArrayValuesRector::class);
    $rectorConfig->rule(SimplifyStrposLowerRector::class);
    $rectorConfig->rule(SingleInArrayToCompareRector::class);
    $rectorConfig->rule(SingularSwitchToIfRector::class);
    $rectorConfig->rule(StrlenZeroToIdenticalEmptyStringRector::class);
    $rectorConfig->rule(StrvalToTypeCastRector::class);
    $rectorConfig->rule(SwitchNegatedTernaryRector::class);
    $rectorConfig->rule(SwitchTrueToIfRector::class);
    $rectorConfig->rule(TernaryEmptyArrayArrayDimFetchToCoalesceRector::class);
    $rectorConfig->rule(TernaryFalseExpressionToIfRector::class);
    $rectorConfig->rule(ThrowWithPreviousExceptionRector::class);
    $rectorConfig->rule(UnnecessaryTernaryExpressionRector::class);
    $rectorConfig->rule(UnwrapSprintfOneArgumentRector::class);
    // Coding Style
    $rectorConfig->rule(ArraySpreadInsteadOfArrayMergeRector::class);
    $rectorConfig->rule(CallUserFuncArrayToVariadicRector::class);
    $rectorConfig->rule(CallUserFuncToMethodCallRector::class);
    $rectorConfig->rule(ConsistentImplodeRector::class);
    $rectorConfig->rule(CountArrayToEmptyArrayComparisonRector::class);
    $rectorConfig->rule(FuncGetArgsToVariadicParamRector::class);
    $rectorConfig->rule(MakeInheritedMethodVisibilitySameAsParentRector::class);
    $rectorConfig->rule(NewlineBeforeNewAssignSetRector::class);
    $rectorConfig->rule(NullableCompareToNullRector::class);
    $rectorConfig->rule(RemoveFinalFromConstRector::class);
    $rectorConfig->rule(RemoveUselessAliasInUseStatementRector::class);
    $rectorConfig->rule(SeparateMultiUseImportsRector::class);
    $rectorConfig->rule(SplitDoubleAssignRector::class);
    $rectorConfig->rule(SplitGroupedClassConstantsRector::class);
    $rectorConfig->rule(SplitGroupedPropertiesRector::class);
    $rectorConfig->rule(StaticArrowFunctionRector::class);
    $rectorConfig->rule(StaticClosureRector::class);
    $rectorConfig->rule(SymplifyQuoteEscapeRector::class);
    $rectorConfig->rule(TernaryConditionVariableAssignmentRector::class);
    $rectorConfig->rule(UseIncrementAssignRector::class);
    $rectorConfig->rule(VersionCompareFuncCallToConstantRector::class);
    $rectorConfig->rule(WrapEncapsedVariableInCurlyBracesRector::class);
    // Removing
    $rectorConfig->rule(ArgumentRemoverRector::class);
    // Renaming
    $rectorConfig->rule(RenameAnnotationRector::class);
    $rectorConfig->rule(RenameConstantRector::class);
};
