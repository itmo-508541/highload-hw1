# PHPUnit 9 — Model Knowledge Patch

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
| `assertRegExp()` | `assertMatchesRegularExpression()` |
| `assertNotRegExp()` | `assertDoesNotMatchRegularExpression()` |
| `assertFileNotExists()` | `assertFileDoesNotExist()` |
| `assertDirectoryNotExists()` | `assertDirectoryDoesNotExist()` |
| `assertNotIsReadable()` | `assertIsNotReadable()` |
| `assertNotIsWritable()` | `assertIsNotWritable()` |
| `assertDirectoryNotIsReadable()` | `assertDirectoryIsNotReadable()` |
| `assertDirectoryNotIsWritable()` | `assertDirectoryIsNotWritable()` |
| `assertFileNotIsReadable()` | `assertFileIsNotReadable()` |
| `assertFileNotIsWritable()` | `assertFileIsNotWritable()` |
| `assertObjectHasAttribute()` | `assertObjectHasProperty()` |
| `assertObjectNotHasAttribute()` | `assertObjectNotHasProperty()` |
| `assertClassHasAttribute()` | Reflection |
| `assertClassNotHasAttribute()` | Reflection |
| `assertClassHasStaticAttribute()` | Reflection |
| `assertClassNotHasStaticAttribute()` | Reflection |
| `assertEqualXMLStructure()` | Alternative XML comparison |
| `assertContains($needle, $string)` | `assertStringContainsString()` |
| `assertNotContains($needle, $string)` | `assertStringNotContainsString()` |
| `MockBuilder::setMethods()` | `onlyMethods()` / `addMethods()` — **no runtime warning** |
| `withConsecutive()` | `willReturnCallback()` or `exactly($n)` + callback |
| `at($index)` | `exactly($n)`, `atLeastOnce()`, callback |
| `getMockClass()` | `createMock()` / `getMockBuilder()` |
| `$this->prophesize()` | Built-in mock/stub (Prophecy removed) |
| `expectDeprecation()` | Explicit behavior verification |
| `expectDeprecationMessage()` | Explicit behavior verification |
| `expectDeprecationMessageMatches()` | Explicit behavior verification |
| `expectError()` | Explicit behavior verification |
| `expectErrorMessage()` | Explicit behavior verification |
| `expectErrorMessageMatches()` | Explicit behavior verification |
| `expectNotice()` | Explicit behavior verification |
| `expectNoticeMessage()` | Explicit behavior verification |
| `expectNoticeMessageMatches()` | Explicit behavior verification |
| `expectWarning()` | Explicit behavior verification |
| `expectWarningMessage()` | Explicit behavior verification |
| `expectWarningMessageMatches()` | Explicit behavior verification |
| `getObjectAttribute()` | Reflection (removed) |
| `getStaticAttribute()` | Reflection (removed) |
| `setUseErrorHandler()` | Removed — do not call |
| `$backupGlobalsBlacklist` | `$backupGlobalsExcludeList` |
| `$backupStaticAttributesBlacklist` | `$backupStaticAttributesExcludeList` |
| `ClassName<*>` in `@covers`/`@uses` | Fully qualified class name — **no runtime warning** |
| Doubling multiple interfaces at once | Removed — one interface per mock |

## Assertions the model may not know about

- `assertObjectEquals($expected, $actual, 'equals')` — value object comparison via object method.
- `assertObjectHasProperty()` / `assertObjectNotHasProperty()` — replaces attribute assertions.
- `assertIsClosedResource()` / `assertIsNotClosedResource()` — a closed resource is not a resource.
- `assertFileEquals()` — no optional parameters; use `*Canonicalizing` / `*IgnoringCase` variants.

## Behavior traps

- `assertSame()` compares `float` **exactly**, no epsilon. For approximate — `assertEqualsWithDelta()`.
- Multiple matchers on the same test double invocation → **exception**. Each matcher must be unique.
- Stubbed method with `iterable` return type → defaults to `[]`.
- Invalid data provider, uninstantiable test case, invalid mock config → **Error**, not Warning.
- PHP deprecations are **not converted** to exceptions by default.
- `readonly class` — PHPUnit throws an error when creating a test double.
- `@coversNothing` can cause files to be excluded from coverage report — do not use without reason.

## Test structure

- One test case class per file; class name must match file name.
- Abstract base test classes: suffix `Test` is deprecated — use `*TestCase`.
- `@preCondition` / `@postCondition` — hooks before/after each test.
- `@depends` can reference a class, not just a method.
- With `forceCoversAnnotation`, a test without `@covers` is risky even without coverage collection.
- `@doesNotPerformAssertions` — test is excluded from code coverage.
