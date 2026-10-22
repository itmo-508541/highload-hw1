# PHPUnit 10 — Model Knowledge Patch

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
| `getMockForAbstractClass()` | `createMock(AbstractClass::class)` — constructor is skipped — **no runtime warning** |
| `getMockForTrait()` | Anonymous class: `new class { use TheTrait; }` — **no runtime warning** |
| `getObjectForTrait()` | Anonymous class: `new class { use TheTrait; }` — **no runtime warning** |
| `createTestProxy()` | `createMock()` + `willReturnCallback()` for pass-through — **no runtime warning** |
| `MockBuilder::addMethods()` | Declare methods in interface/class, use `onlyMethods()` — **no runtime warning** |
| `$this->returnValue($val)` | `$double->willReturn($val)` — **no runtime warning** |
| `$this->returnArgument($idx)` | `$double->willReturnArgument($idx)` — **no runtime warning** |
| `$this->returnCallback($cb)` | `$double->willReturnCallback($cb)` — **no runtime warning** |
| `$this->returnSelf()` | `$double->willReturnSelf()` — **no runtime warning** |
| `$this->returnValueMap($map)` | `$double->willReturnMap($map)` — **no runtime warning** |
| `$this->onConsecutiveCalls(...)` | `$double->willReturn($v1, $v2, ...)` or `willReturnCallback()` — **no runtime warning** |
| `$backupGlobalsExcludeList` | `#[ExcludeGlobalVariableFromBackup('var')]` — property removed |
| `$backupStaticAttributesExcludeList` | `#[ExcludeStaticPropertyFromBackup('class', 'prop')]` — property removed |

## Assertions the model may not know about

- `assertIsList($array)` — asserts array is a list (sequential 0-based integer keys).
- `assertStringEqualsStringIgnoringLineEndings($expected, $actual)` — normalizes `\r\n` to `\n`.
- `assertStringContainsStringIgnoringLineEndings($needle, $haystack)` — same normalization.
- `assertFileMatchesFormat()` / `assertFileMatchesFormatFile()` — format comparison for file content.
- `createConfiguredStub(Foo::class, ['method' => $value])` — one-liner for configured stubs.

## Behavior traps

- **`createStub()` does not verify expectations.** Calling `->expects()` on a stub is silently ignored. Use `createMock()` when verifying invocations.
- **`Assert` and `TestCase` public methods are `final`.** Overriding `assertSame()` or similar in a base test class is a fatal error.
- **Data providers must be `public static`.** Non-static providers trigger a hard deprecation. Write `public static function provider()`.
- **Data providers must not declare parameters.** Declaring parameters is deprecated.
- **Stubbed method called more times than configured → error.** If `willReturn($a, $b)` is set for 2 calls but the method is called 3 times, PHPUnit throws. Use `willReturnCallback()` for open-ended repetition.
- **`readonly class` cannot be doubled.** PHPUnit throws an error when trying to create a test double.
- **`#[IgnoreDeprecations]`** — suppresses `E_(USER_)DEPRECATED` issues for a test; use it instead of workaround code.
- **`#[WithoutErrorHandler]`** — disables PHPUnit's error handler for a test method; use when testing code that needs `error_get_last()` or custom error handling.
- **`createStub()` / `createConfiguredStub()` are `static`** — usable from static data provider methods.

## Test structure

- Abstract base test classes: do not suffix with `Test` — use `*TestCase`.
- `#[ExcludeGlobalVariableFromBackup('var')]` — replaces `$backupGlobalsExcludeList` property.
- `#[ExcludeStaticPropertyFromBackup('class', 'prop')]` — replaces `$backupStaticAttributesExcludeList` property.
- PHP 8.1+ required — use enums, readonly, fibers, intersection types freely.
