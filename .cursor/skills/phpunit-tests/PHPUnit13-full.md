# PHPUnit 13 — Model Knowledge Patch

## Banned calls → replacements

| Do not write | Write instead |
|---|---|
| `assertRegExp()` | `assertMatchesRegularExpression()` — **removed** |
| `assertNotRegExp()` | `assertDoesNotMatchRegularExpression()` — **removed** |
| `assertFileNotExists()` | `assertFileDoesNotExist()` — **removed** |
| `assertDirectoryNotExists()` | `assertDirectoryDoesNotExist()` — **removed** |
| `assertNotIsReadable()` | `assertIsNotReadable()` — **removed** |
| `assertNotIsWritable()` | `assertIsNotWritable()` — **removed** |
| `assertDirectoryNotIsReadable()` | `assertDirectoryIsNotReadable()` — **removed** |
| `assertDirectoryNotIsWritable()` | `assertDirectoryIsNotWritable()` — **removed** |
| `assertFileNotIsReadable()` | `assertFileIsNotReadable()` — **removed** |
| `assertFileNotIsWritable()` | `assertFileIsNotWritable()` — **removed** |
| `assertObjectHasAttribute()` | `assertObjectHasProperty()` — **removed** |
| `assertObjectNotHasAttribute()` | `assertObjectNotHasProperty()` — **removed** |
| `assertClassHasAttribute()` / `assertClassNotHasAttribute()` | Reflection — **removed** |
| `assertClassHasStaticAttribute()` / `assertClassNotHasStaticAttribute()` | Reflection — **removed** |
| `assertEqualXMLStructure()` | Alternative XML comparison — **removed** |
| `assertContains($needle, $string)` | `assertStringContainsString()` — **removed** |
| `assertNotContains($needle, $string)` | `assertStringNotContainsString()` — **removed** |
| `assertContainsOnly('int', $arr)` | `assertContainsOnlyInt($arr)` (type-specific variant per type) — **removed** |
| `assertNotContainsOnly('string', $arr)` | `assertContainsNotOnlyString($arr)` (type-specific variant per type) — **removed** |
| `assertStringNotMatchesFormat()` / `assertStringNotMatchesFormatFile()` | No replacement — **removed** |
| `isType('array')` / `containsOnly('int')` | `isArray()` / `containsOnlyInt()` — **removed** |
| `MockBuilder::setMethods()` | `onlyMethods()` — **removed** |
| `MockBuilder::addMethods()` | Declare methods in interface/class, use `onlyMethods()` — **removed** |
| `getMockForAbstractClass()` / `MockBuilder::getMockForAbstractClass()` | `createMock(AbstractClass::class)` — constructor is skipped — **removed** |
| `getMockForTrait()` / `getObjectForTrait()` | `new class { use TheTrait; }` — **removed** |
| `createTestProxy()` | `createMock()` + `willReturnCallback()` for pass-through — **removed** |
| `getMockFromWsdl()` | Manual stub or interface mock — **removed** |
| `getMockClass()` | `createMock()` / `getMockBuilder()` — **removed** |
| `$this->prophesize()` | Built-in mock/stub — **removed** (Prophecy removed) |
| `$this->returnValue($val)` | `$double->willReturn($val)` — **removed** |
| `$this->returnArgument($idx)` | `$double->willReturnArgument($idx)` — **removed** |
| `$this->returnCallback($cb)` | `$double->willReturnCallback($cb)` — **removed** |
| `$this->returnSelf()` | `$double->willReturnSelf()` — **removed** |
| `$this->returnValueMap($map)` | `$double->willReturnMap($map)` — **removed** |
| `$this->onConsecutiveCalls(...)` | `$double->willReturn($v1, $v2, ...)` or `willReturnCallback()` — **removed** |
| `withConsecutive()` | `withParameterSetsInOrder([...], [...])` / `withParameterSetsInAnyOrder([...], [...])` — **removed** |
| Manual `withConsecutive` workaround via callback | `withParameterSetsInOrder()` / `withParameterSetsInAnyOrder()` — native API available |
| `at($index)` | `exactly($n)`, `atLeastOnce()`, callback — **removed** |
| `$stub->expects(...)` | Use `createMock()` for expectations — `expects()` on stubs **removed** |
| `any()` | `atLeastOnce()` / `exactly($n)` or use test stub — **hard deprecated** |
| `$mock->with(...)` without `expects()` | Add `expects()` or use stub without `with*()` — **hard deprecated** |
| `atLeast(0)` | `atLeastOnce()` or `atLeast(1)` — non-positive arg **hard deprecated** |
| `id('label')` / `after('label')` | Remove — **soft deprecated, no runtime warning** |
| `expectExceptionMessage($msg)` | `expectExceptionMessageIsOrContains($msg)` — **soft deprecated, no runtime warning** |
| `@test`, `@covers`, `@dataProvider`, `@depends`, `@group`, etc. | `#[Test]`, `#[CoversClass]`, `#[DataProvider]`, `#[Depends]`, `#[Group]` — annotations **removed** |
| `#[CoversClass(MyTrait::class)]` / `#[UsesClass(MyTrait::class)]` | `#[CoversTrait(MyTrait::class)]` / `#[UsesTrait(MyTrait::class)]` — **removed** |
| `#[CoversNothing]` on test method | `#[CoversNothing]` on class only — **removed** |
| `#[RunClassInSeparateProcess]` | `#[RunTestsInSeparateProcesses]` — **removed** |
| Non-static data provider | `public static function provider(): array` — **removed** (fatal) |
| Data provider with parameters | No parameters allowed — **removed** (fatal) |
| `$backupGlobalsBlacklist` | `#[ExcludeGlobalVariableFromBackup('var')]` — **removed** |
| `$backupGlobalsExcludeList` | `#[ExcludeGlobalVariableFromBackup('var')]` — **removed** |
| `$backupStaticAttributesBlacklist` | `#[ExcludeStaticPropertyFromBackup('class', 'prop')]` — **removed** |
| `$backupStaticAttributesExcludeList` | `#[ExcludeStaticPropertyFromBackup('class', 'prop')]` — **removed** |
| `expectDeprecation()` / `expectDeprecationMessage()` | `expectUserDeprecationMessage($msg)` — **removed** |
| `expectDeprecationMessageMatches()` | `expectUserDeprecationMessageMatches($regex)` — **removed** |
| `expectError()` / `expectErrorMessage()` / `expectErrorMessageMatches()` | Explicit behavior verification — **removed** |
| `expectNotice()` / `expectNoticeMessage()` / `expectNoticeMessageMatches()` | Explicit behavior verification — **removed** |
| `expectWarning()` / `expectWarningMessage()` / `expectWarningMessageMatches()` | Explicit behavior verification — **removed** |
| `getObjectAttribute()` / `getStaticAttribute()` | Reflection — **removed** |
| `setUseErrorHandler()` | Do not call — **removed** |
| `TestCase::iniSet()` | `ini_set()` in `setUp()` + `ini_restore()` in `tearDown()` — **removed** |
| `TestCase::setLocale()` | `setlocale()` manually — **removed** |
| Doubling multiple interfaces at once | One interface per mock — **removed** |
| Doubling interface/class with a method named `method` | Refactor — PHPUnit's `->method('name')` collides — **removed** |

## Assertions the model may not know about

- `assertObjectEquals($expected, $actual, 'equals')` — value object comparison via object method.
- `assertObjectNotEquals($expected, $actual, 'equals')` — inverse of `assertObjectEquals()`.
- `assertObjectHasProperty()` / `assertObjectNotHasProperty()` — replaces attribute assertions.
- `assertIsClosedResource()` / `assertIsNotClosedResource()` — a closed resource is not a resource.
- `assertFileEquals()` — no optional parameters; use `*Canonicalizing` / `*IgnoringCase` variants.
- `assertIsList($array)` — asserts array is a list (sequential 0-based integer keys).
- `assertStringEqualsStringIgnoringLineEndings($expected, $actual)` — normalizes `\r\n` to `\n`.
- `assertStringContainsStringIgnoringLineEndings($needle, $haystack)` — same normalization.
- `assertFileMatchesFormat()` / `assertFileMatchesFormatFile()` — format comparison for file content.
- `createConfiguredStub(Foo::class, ['method' => $value])` — one-liner for configured stubs.
- `expectUserDeprecationMessage($msg)` — asserts `E_USER_DEPRECATED` with exact message.
- `expectUserDeprecationMessageMatches($regex)` — regex variant of above.
- `assertContainsOnlyInt($arr)` / `assertContainsOnlyString($arr)` / etc. — type-specific variants for all scalar types, `object`, `callable`, `iterable`, `resource`, `closedResource`.
- `assertContainsNotOnlyInstancesOf(Foo::class, $arr)` — inverse of `assertContainsOnlyInstancesOf()`.
- `expectErrorLog($msg)` — asserts `error_log()` output from the test.
- `getStubBuilder(Foo::class)` — fluent builder for test stubs, analogous to `getMockBuilder()`.
- `expectExceptionObject()` accepts `Throwable` (not just `Exception`).
- `assertArraysAreIdentical($expected, $actual)` / `assertArraysAreEqual($expected, $actual)` — compare full arrays (keys+values).
- `assertArraysAreIdenticalIgnoringOrder()` / `assertArraysAreEqualIgnoringOrder()` — same but key order irrelevant.
- `assertArraysHaveIdenticalValues()` / `assertArraysHaveEqualValues()` — only values, ignore keys.
- `assertArraysHaveIdenticalValuesIgnoringOrder()` / `assertArraysHaveEqualValuesIgnoringOrder()` — values only, any order.
- `withParameterSetsInOrder([[$a1], [$a2]])` — consecutive-call expectations with argument matching.
- `withParameterSetsInAnyOrder([[$a1], [$a2]])` — same, order-independent.
- `expectExceptionMessageIsOrContains($msg)` — replaces `expectExceptionMessage()`.
- `#[DataProviderClosure]` — static closure as inline data provider (see Test structure).

## Behavior traps

- **`assertSame()` compares `float` exactly**, no epsilon. For approximate — `assertEqualsWithDelta()`.
- **Multiple matchers on the same test double invocation → exception.** Each matcher must be unique.
- **Stubbed method with `iterable` return type → defaults to `[]`.**
- **Invalid data provider, uninstantiable test case, invalid mock config → Error**, not Warning.
- **PHP deprecations are not converted to exceptions** by default.
- **`readonly class` CAN be doubled.** `createMock(ReadonlyClass::class)` works.
- **`Assert` and `TestCase` public methods are `final`.** Overriding `assertSame()` or similar in a base test class is a fatal error.
- **Data providers must be `public static`.** Non-static → fatal error.
- **Data providers must not declare parameters.** Declaring parameters → fatal error.
- **Stubbed method called more times than configured → error.** If `willReturn($a, $b)` is set for 2 calls but the method is called 3 times, PHPUnit throws. Use `willReturnCallback()` for open-ended repetition.
- **`createStub()` does not verify expectations.** Calling `->expects()` on a stub is a fatal error. Use `createMock()` when verifying invocations.
- **`createStub()` / `createConfiguredStub()` are `static`** — usable from static data provider methods.
- **`#[CoversClass(Foo::class)]` implicitly covers abstract parent classes.** No need to add separate `#[CoversClass]` for the parent.
- **`willReturnMap()` and nullable parameters:** if a parameter defaults to `null` and no argument is passed, the map lookup works correctly.
- **JSON assertions treat objects as unordered.** `assertJsonStringEqualsJsonString()` compares object keys regardless of order.
- **Data provider method name starting with `test` emits a warning.** Name providers `dataForFoo()` or `providerFoo()`, not `testFoo()`.
- **Property Hooks** (PHP 8.4 `get`/`set` hooks) are supported in test doubles — no workaround needed.
- **Mock object without configured expectations → PHPUnit notice.** Suppress per-test with `#[AllowMockObjectsWithoutExpectations]`.
- **`TestCase::__construct()` is `final`** — cannot be overridden.
- **Mixing `#[DataProvider]` with `#[TestWith]` on the same method → warning.**
- **`willReturnMap()` is strict.** Supports constraints as map arguments; unmatched calls return `null` explicitly. Review existing maps after upgrade.
- **Assertion failure inside `willReturnCallback()` propagates** even when code under test catches exceptions. Do not rely on swallowed assertion failures.
- **Anonymous classes cannot be doubled.** `createMock()` / `createStub()` on an anonymous class throws `InvalidClassNameException`.
- **`expectOutputString()` / `expectOutputRegex()` cannot be called twice.** Second call throws instead of silently replacing the first.
- **Sealed test doubles.** When `requireSealedMockObjects="true"` is configured, calling an unconfigured method on a mock/stub throws. Explicitly stub every method the code under test calls.
- **`#[RunTestsInSeparateProcesses]` inherits from parent class.** Do not repeat in subclasses — it has no effect and may confuse readers.
- PHP 8.4+ required — use property hooks, `new` in initializers, `#[\Override]` freely.

## Test structure

- One test case class per file; class name must match file name.
- Abstract base test classes: do not suffix with `Test` — use `*TestCase`.
- Metadata via attributes only. All doc-comment annotations (`@test`, `@covers`, `@dataProvider`, etc.) are removed — fatal error.
- `#[ExcludeGlobalVariableFromBackup('var')]` — replaces `$backupGlobalsExcludeList` property.
- `#[ExcludeStaticPropertyFromBackup('class', 'prop')]` — replaces `$backupStaticAttributesExcludeList` property.
- `#[CoversMethod(Foo::class, 'bar')]` / `#[UsesMethod(Foo::class, 'bar')]` — fine-grained method-level coverage.
- `#[CoversTrait(Foo::class)]` / `#[UsesTrait(Foo::class)]` — explicit trait coverage (do not use `#[CoversClass]` for traits).
- `#[CoversNamespace('App\Service')]` / `#[UsesNamespace('App\Service')]` — namespace-level coverage.
- `#[CoversClassesThatExtendClass(Base::class)]` / `#[CoversClassesThatImplementInterface(Iface::class)]` — hierarchical coverage.
- `#[DisableReturnValueGenerationForTestDoubles]` — forces explicit stub configuration; unconfigured methods throw instead of returning default.
- `#[TestWith([1, 2, 3])]` supports named data sets: `#[TestWith([1, 2], 'positive')]`.
- Data providers can supply named arguments matching test method parameter names.
- `#[DataProviderClosure]` — attach a `static fn(): array` directly as a data provider, no separate method needed.
- `#[DataProvider('provider', skipWhenEmpty: true)]` — skips the test instead of marking it risky when the provider yields no data sets.
- `#[WithEnvironmentVariable('VAR', 'value')]` — sets env var for test duration.
- `#[RequiresEnvironmentVariable('VAR')]` — skip test if env var is not set.
- `#[AllowMockObjectsWithoutExpectations]` — suppresses notice for mocks without expectations (class or method level).
- `#[IgnorePHPUnitWarnings]` — suppresses PHPUnit warnings for a test.
- `#[IgnoreDeprecations('/pattern/')]` — supports regex to selectively ignore deprecations.
- `#[WithoutErrorHandler]` — disables PHPUnit's error handler for a test method; use when testing code that needs `error_get_last()` or custom error handling.
- `withParameterSetsInOrder()` / `withParameterSetsInAnyOrder()` — use on `InvocationMocker` after `expects()` + `method()` to replace manual consecutive-call stubs.
