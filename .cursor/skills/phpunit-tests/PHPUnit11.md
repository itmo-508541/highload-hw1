# PHPUnit 11 — Model Knowledge Patch

Этот файл корректирует ошибки, которые модель допускает при генерации PHPUnit-тестов.
Содержит только то, что модель вероятнее всего напишет неправильно.

Принципы составления:
- Формат «не пиши → пиши вместо» — модель должна немедленно понять, что заменить.
- Только факты, меняющие код `*Test.php`. CLI, xml-конфиг, PHAR, логгеры — вне скоупа.
- Не включать то, что модель и так знает правильно (тратит токены без пользы).
- Не включать номера минорных версий — файл привязан к мажорной версии целиком.
- Если deprecated API не выбрасывает runtime warning — указать явно (модель не получит сигнал об ошибке от PHPUnit и продолжит использовать deprecated вызов).
- Каждый пункт — самодостаточный; порядок чтения не важен.
- Писать на английском (кроме этого описания) — экономит токены и совпадает с языком API.

---

## Banned calls → replacements

| Do not write | Write instead |
|---|---|
| `@test`, `@covers`, `@dataProvider`, `@depends`, `@group` | `#[Test]`, `#[CoversClass]`, `#[DataProvider]`, `#[Depends]`, `#[Group]` — annotations **hard deprecated** |
| `@covers ClassName` (for trait) | `#[CoversTrait(Foo::class)]` / `#[UsesTrait(Foo::class)]` |
| `$stub->expects($this->once())` | Use `createMock()` when verifying invocations — `expects()` on stubs is **hard deprecated** |
| `getMockForAbstractClass()` | `createMock(AbstractClass::class)` — **hard deprecated** |
| `getMockFromWsdl()` | Manual stub or interface mock — **hard deprecated** |
| `getMockForTrait()` | `new class { use TheTrait; }` — **hard deprecated** |
| `getObjectForTrait()` | `new class { use TheTrait; }` — **hard deprecated** |
| `createTestProxy()` | `createMock()` + `willReturnCallback()` — **hard deprecated** |
| `MockBuilder::addMethods()` | Declare methods in interface/class, use `onlyMethods()` — **hard deprecated** |
| `MockBuilder::getMockForAbstractClass()` | `createMock()` — **hard deprecated** |
| `$this->returnValue($val)` | `$double->willReturn($val)` — **hard deprecated** |
| `$this->returnArgument($idx)` | `$double->willReturnArgument($idx)` — **hard deprecated** |
| `$this->returnCallback($cb)` | `$double->willReturnCallback($cb)` — **hard deprecated** |
| `$this->returnSelf()` | `$double->willReturnSelf()` — **hard deprecated** |
| `$this->returnValueMap($map)` | `$double->willReturnMap($map)` — **hard deprecated** |
| `$this->onConsecutiveCalls(...)` | `$double->willReturn($v1, $v2, ...)` or `willReturnCallback()` — **hard deprecated** |
| `TestCase::iniSet()` | `ini_set()` in `setUp()` + `ini_restore()` in `tearDown()` — **hard deprecated** |
| `TestCase::setLocale()` | `setlocale()` manually — **hard deprecated** |
| `assertContainsOnly('int', $arr)` | `assertContainsOnlyInt($arr)` — **soft deprecated, no runtime warning** |
| `assertNotContainsOnly('string', $arr)` | `assertContainsNotOnlyString($arr)` — **soft deprecated, no runtime warning** |
| `isType('array')` | `isArray()`, `isBool()`, `isInt()`, etc. — **soft deprecated, no runtime warning** |
| `assertStringNotMatchesFormat()` | No replacement — **hard deprecated** |
| Non-static data provider | `public static function provider(): array` — **removed, fatal** |
| Data provider with parameters | No parameters allowed — **removed, fatal** |

## Assertions the model may not know about

- `expectUserDeprecationMessage($msg)` — asserts `E_USER_DEPRECATED` with exact message. Replaces removed `expectDeprecation()`.
- `expectUserDeprecationMessageMatches($regex)` — regex variant of above.
- `assertObjectNotEquals($expected, $actual, 'equals')` — inverse of `assertObjectEquals()`.
- `assertContainsOnlyInt($arr)` / `assertContainsOnlyString($arr)` / etc. — type-specific variants for all scalar types, `object`, `callable`, `iterable`, `resource`, `closedResource`.
- `assertContainsNotOnlyInstancesOf(Foo::class, $arr)` — inverse of `assertContainsOnlyInstancesOf()`.

## Behavior traps

- **`readonly class` CAN be doubled.** Since PHPUnit 11.2, `createMock(ReadonlyClass::class)` works.
- **`createStub()` does not verify expectations.** Calling `->expects()` on a stub triggers a hard deprecation. Use `createMock()` when verifying invocations.
- **`#[CoversClass(Foo::class)]` implicitly covers abstract parent classes.** No need to add separate `#[CoversClass]` for the parent.
- **Doubling interface/class with a method named `method` is deprecated.** PHPUnit's `->method('name')` collides with a real `method()` — refactor or use `willReturnCallback()` with `$invocation->getMethodName()`.
- **`willReturnMap()` and nullable parameters:** if a parameter defaults to `null` and no argument is passed, the map lookup works correctly (fixed).
- **JSON assertions treat objects as unordered.** `assertJsonStringEqualsJsonString()` compares object keys regardless of order.
- **Data provider method name starting with `test` emits a warning.** Name providers `dataForFoo()` or `providerFoo()`, not `testFoo()`.
- **Property Hooks** (PHP 8.4 `get`/`set` hooks) are supported in test doubles — no workaround needed.
- **Stubbed method called more times than configured → error.** `willReturn($a, $b)` for 2 calls, 3rd call throws. Use `willReturnCallback()` for open-ended repetition.

## Test structure

- Metadata via attributes only. All doc-comment annotations (`@test`, `@covers`, `@dataProvider`, etc.) are hard-deprecated.
- `#[CoversMethod(Foo::class, 'bar')]` / `#[UsesMethod(Foo::class, 'bar')]` — fine-grained method-level coverage.
- `#[CoversTrait(Foo::class)]` / `#[UsesTrait(Foo::class)]` — explicit trait coverage (do not use `#[CoversClass]` for traits).
- `#[DisableReturnValueGenerationForTestDoubles]` — forces explicit stub configuration; unconfigured methods throw instead of returning default.
- `#[TestWith([1, 2, 3])]` supports named data sets: `#[TestWith([1, 2], 'positive')]`.
- Data providers can supply named arguments matching test method parameter names.
- PHP 8.2+ required — use `readonly class`, DNF types, `enum` freely.

