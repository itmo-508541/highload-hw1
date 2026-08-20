# PHPUnit 13 — Model Knowledge Patch

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
| `assertContainsOnly('int', $arr)` | `assertContainsOnlyInt($arr)` — **removed** |
| `assertNotContainsOnly('string', $arr)` | `assertContainsNotOnlyString($arr)` — **removed** |
| `isType('array')` / `containsOnly('int')` | `isArray()` / `containsOnlyInt()` — **removed** |
| `any()` | Test stub or `atLeastOnce()` / `exactly($n)` — **hard deprecated** |
| `$mock->with(...)` without `expects()` | Add `expects()` or use stub without `with*()` — **hard deprecated** |
| `atLeast(0)` | `atLeastOnce()` or `atLeast(1)` — non-positive arg **hard deprecated** |
| `expectExceptionMessage($msg)` | `expectExceptionMessageIsOrContains($msg)` — **soft deprecated, no runtime warning** |
| `id('label')` / `after('label')` | Remove — **soft deprecated, no runtime warning** |
| `#[CoversNothing]` on test method | `#[CoversNothing]` on class only — **removed** |
| `#[RunClassInSeparateProcess]` | `#[RunTestsInSeparateProcesses]` — **removed** |
| `withConsecutive(...)` workaround via callback | `withParameterSetsInOrder([...], [...])` / `withParameterSetsInAnyOrder([...], [...])` — native API |

## Assertions the model may not know about

- `assertArraysAreIdentical($expected, $actual)` / `assertArraysAreEqual($expected, $actual)` — compare full arrays (keys+values).
- `assertArraysAreIdenticalIgnoringOrder()` / `assertArraysAreEqualIgnoringOrder()` — same but key order irrelevant.
- `assertArraysHaveIdenticalValues()` / `assertArraysHaveEqualValues()` — only values, ignore keys.
- `assertArraysHaveIdenticalValuesIgnoringOrder()` / `assertArraysHaveEqualValuesIgnoringOrder()` — values only, any order.
- `withParameterSetsInOrder([[$a1], [$a2]])` — consecutive-call expectations with argument matching (replaces manual `withConsecutive` patterns).
- `withParameterSetsInAnyOrder([[$a1], [$a2]])` — same, order-independent.
- `expectExceptionMessageIsOrContains($msg)` — replaces `expectExceptionMessage()`.
- `#[DataProviderClosure]` — static closure as inline data provider.

## Behavior traps

- **`willReturnMap()` is strict.** Supports constraints as map arguments; unmatched calls return `null` explicitly. Review existing maps after upgrade.
- **Assertion failure inside `willReturnCallback()` propagates** even when code under test catches exceptions. Do not rely on swallowed assertion failures.
- **Anonymous classes cannot be doubled.** `createMock()` / `createStub()` on an anonymous class throws `InvalidClassNameException`.
- **`expectOutputString()` / `expectOutputRegex()` cannot be called twice.** Second call throws instead of silently replacing the first.
- **Sealed test doubles.** When `requireSealedMockObjects="true"` is configured, calling an unconfigured method on a mock/stub throws. Explicitly stub every method the code under test calls.
- **`#[RunTestsInSeparateProcesses]` inherits from parent class.** Do not repeat in subclasses — it has no effect and may confuse readers.
- PHP 8.4+ required — use property hooks, `new` in initializers, `#[\Override]` freely.

## Test structure

- `#[DataProviderClosure]` — attach a `static fn(): array` directly as a data provider, no separate method needed.
- `#[DataProvider('provider', skipWhenEmpty: true)]` — skips the test instead of marking it risky when the provider yields no data sets.
- `withParameterSetsInOrder()` / `withParameterSetsInAnyOrder()` — use on `InvocationMocker` after `expects()` + `method()` to replace manual consecutive-call stubs.
