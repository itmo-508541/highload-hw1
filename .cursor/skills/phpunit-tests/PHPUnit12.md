# PHPUnit 12 — Model Knowledge Patch

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
| `@test`, `@covers`, `@dataProvider`, `@depends`, `@group`, etc. | `#[Test]`, `#[CoversClass]`, `#[DataProvider]`, `#[Depends]`, `#[Group]` — doc-comment annotations **removed** |
| `#[CoversClass(MyTrait::class)]` | `#[CoversTrait(MyTrait::class)]` — trait targeting via `CoversClass`/`UsesClass` **removed** |
| `getMockForAbstractClass(Foo::class)` | `createMock(Foo::class)` — **removed** |
| `getMockForTrait()` / `getObjectForTrait()` | `new class { use TheTrait; }` — **removed** |
| `createTestProxy(Foo::class)` | `createMock()` + `willReturnCallback()` — **removed** |
| `MockBuilder::addMethods(['foo'])` | Declare `foo()` in interface/class, use `onlyMethods()` — **removed** |
| `$stub->expects($this->once())` | `createMock()` for expectations — `expects()` on stubs **removed** |
| `$this->returnValue()`, `returnArgument()`, `returnCallback()`, `returnSelf()`, `returnValueMap()`, `onConsecutiveCalls()` | `$double->willReturn()` / `willReturnArgument()` / `willReturnCallback()` / `willReturnSelf()` / `willReturnMap()` — `TestCase` return-config helpers **removed** |
| `TestCase::iniSet()` | `ini_set()` + `ini_restore()` manually — **removed** |
| `TestCase::setLocale()` | `setlocale()` manually — **removed** |
| `assertStringNotMatchesFormat()` / `assertStringNotMatchesFormatFile()` | Removed — no replacement |
| `assertContainsOnly('int', $arr)` | `assertContainsOnlyInt($arr)` — type-specific variant per type — **hard deprecated** |
| `assertNotContainsOnly('string', $arr)` | `assertContainsNotOnlyString($arr)` — **hard deprecated** |
| `isType('array')` / `containsOnly('int')` | `isArray()` / `containsOnlyInt()` — **hard deprecated** |
| `$stub->with(...)` / `$stub->withAnyParameters()` | No effect on stubs — use `createMock()` for argument matching — **hard deprecated** |
| `any()` | Test stub or specific matcher (`once()`, `atLeastOnce()`, `exactly($n)`) — **soft deprecated, no runtime warning** |
| `#[CoversNothing]` on a test method | Use `#[CoversNothing]` on class level only — **hard deprecated** |
| `#[RunClassInSeparateProcess]` | `#[RunTestsInSeparateProcesses]` — **hard deprecated** |
| Doubling interface with a method named `method` | Refactor — PHPUnit's `->method('name')` collides — **removed** |

## Assertions the model may not know about

- `expectErrorLog($msg)` — asserts `error_log()` output from the test.
- `getStubBuilder(Foo::class)` — fluent builder for test stubs, analogous to `getMockBuilder()`.
- `expectExceptionObject()` accepts `Throwable` (not just `Exception`).

## Behavior traps

- Mock object without configured expectations → PHPUnit notice. Suppress per-test with `#[AllowMockObjectsWithoutExpectations]`.
- `TestCase::__construct()` is `final` — cannot be overridden.
- `readonly class` can be doubled — no workaround needed.
- `$stub->with(...)` silently does nothing on a stub; only effective on mocks via `createMock()`.
- Mixing `#[DataProvider]` with `#[TestWith]` on the same method → warning.
- Data provider method name starting with `test` → warning.
- PHP 8.3+ required — PHP 8.2 support removed.

## Test structure

- Attributes only. All doc-comment annotations removed — `@test`, `@covers`, `@dataProvider` etc. are fatal.
- `#[WithEnvironmentVariable('VAR', 'value')]` — sets env var for test duration.
- `#[RequiresEnvironmentVariable('VAR')]` — skip test if env var is not set.
- `#[CoversNamespace('App\Service')]` / `#[UsesNamespace('App\Service')]` — namespace-level coverage.
- `#[CoversClassesThatExtendClass(Base::class)]` / `#[CoversClassesThatImplementInterface(Iface::class)]` — hierarchical coverage.
- `#[AllowMockObjectsWithoutExpectations]` — suppresses notice for mocks without expectations (class or method level).
- `#[IgnorePHPUnitWarnings]` — suppresses PHPUnit warnings for a test.
- `#[IgnoreDeprecations('/pattern/')]` — supports regex to selectively ignore deprecations.
