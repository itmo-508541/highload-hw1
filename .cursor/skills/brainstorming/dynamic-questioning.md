# Динамическая генерация вопросов (Dynamic Question Generation)

> **ПРИНЦИП:** Вопросы нужны не ради сбора данных, а чтобы **выявить архитектурные последствия**.
>
> Каждый вопрос должен вести к конкретному решению по реализации, влияющему на cost, complexity или timeline.

---

## 🧠 Основные принципы

### 1. Questions Reveal Consequences

Хороший вопрос — не «какой цвет?», а:

```markdown
❌ BAD: "What authentication method?"
✅ GOOD: "Should users sign up with email/password or social login?

   Impact:
   - Email/Pass → Need password reset, hashing, 2FA infrastructure
   - Social → OAuth providers, user profile mapping, less control

   Trade-off: Security vs. Development time vs. User friction"
```

### 2. Context Before Content

Сначала выяснить, **куда** попадает запрос:

| Context | Question Focus |
|---------|----------------|
| **Greenfield** (новый проект) | Foundation: stack, hosting, scale |
| **Feature Addition** | Integration points, существующие паттерны, breaking changes |
| **Refactor** | Зачем refactor? Performance? Maintainability? Что сломано? |
| **Debug** | Symptoms → Root cause → Reproduction path |

### 3. Minimum Viable Questions

**ПРИНЦИП:** Каждый вопрос должен убрать развилку в дорожной карте реализации.

```
Before Question:
├── Path A: Do X (5 min)
├── Path B: Do Y (15 min)
└── Path C: Do Z (1 hour)

After Question:
└── Path Confirmed: Do X (5 min)
```

Если вопрос не сужает варианты реализации → **удалить его**.

### 4. Questions Generate Data, Not Assumptions

```markdown
❌ ASSUMPTION: "User probably wants Stripe for payments"
✅ QUESTION: "Which payment provider fits your needs?

   Stripe → Best documentation, 2.9% + $0.30, US-centric
   LemonSqueezy → Merchant of Record, 5% + $0.50, global taxes
   Paddle → Complex pricing, handles EU VAT, enterprise focus"
```

---

## 📋 Алгоритм генерации вопросов

```
INPUT: User request + Context (greenfield/feature/refactor/debug)
│
├── STEP 1: Parse Request
│   ├── Extract domain (ecommerce, auth, realtime, cms, etc.)
│   ├── Extract features (явные и подразумеваемые)
│   └── Extract scale indicators (users, data volume, frequency)
│
├── STEP 2: Identify Decision Points
│   ├── Что MUST решить до кода? (blocking)
│   ├── Что можно отложить? (deferable)
│   └── Что даёт ARCHITECTURAL impact? (high-leverage)
│
├── STEP 3: Generate Questions (Priority Order)
│   ├── P0: Blocking decisions (нельзя идти дальше без ответа)
│   ├── P1: High-leverage (>30% реализации)
│   ├── P2: Medium-leverage (отдельные фичи)
│   └── P3: Nice-to-have (edge cases, optimization)
│
└── STEP 4: Format Each Question
    ├── What: ясный вопрос
    ├── Why: влияние на реализацию
    ├── Options: trade-offs (не просто A vs B)
    └── Default: что делать, если пользователь не ответил
```

---

## 🎯 Question banks по доменам

### E-Commerce

| Question | Why It Matters | Trade-offs |
|----------|----------------|------------|
| **Single or Multi-vendor?** | Multi-vendor → commission, vendor dashboards, split payments | +Revenue, -Complexity |
| **Inventory Tracking?** | Stock tables, reservation, low-stock alerts | +Accuracy, -Development time |
| **Digital or Physical Products?** | Digital → download links, без shipping | Physical → shipping APIs, tracking |
| **Subscription or One-time?** | Subscription → recurring billing, dunning, proration | +Revenue, -Complexity |

### Authentication

| Question | Why It Matters | Trade-offs |
|----------|----------------|------------|
| **Social Login Needed?** | OAuth vs. password reset infrastructure | +UX, -Control |
| **Role-Based Permissions?** | RBAC, policy enforcement, admin UI | +Security, -Development time |
| **2FA Required?** | TOTP/SMS, backup codes, recovery flow | +Security, -UX friction |
| **Email Verification?** | Tokens, email service, resend logic | +Security, -Sign-up friction |

### Real-time

| Question | Why It Matters | Trade-offs |
|----------|----------------|------------|
| **WebSocket or Polling?** | WS → scaling, connection management | Polling → проще, выше latency |
| **Expected Concurrent Users?** | <100 → single server; >1000 → Redis pub/sub; >10k → specialized infra | +Scale, -Complexity |
| **Message Persistence?** | History, storage, pagination | +UX, -Storage |
| **Ephemeral or Durable?** | Ephemeral → in-memory; Durable → DB write before emit | +Reliability, -Latency |

### Content / CMS

| Question | Why It Matters | Trade-offs |
|----------|----------------|------------|
| **Rich Text or Markdown?** | Rich Text → sanitization, XSS | Markdown → проще, без WYSIWYG |
| **Draft/Publish Workflow?** | Status, scheduled jobs, versioning | +Control, -Complexity |
| **Media Handling?** | Upload, storage, optimization | +Features, -Development time |
| **Multi-language?** | i18n, translation UI, fallback | +Reach, -Complexity |

---

## 📐 Динамический шаблон вопроса

Имена полей — на английском:

```markdown
Based on your request for [DOMAIN] [FEATURE]:

## 🔴 CRITICAL (Blocking Decisions)

### 1. **[DECISION POINT]**

**Question:** [Clear, specific question]

**Why This Matters:**
- [Explain architectural consequence]
- [Affects: cost / complexity / timeline / scale]

**Options:**
| Option | Pros | Cons | Best For |
|--------|------|------|----------|
| A | [Advantage] | [Disadvantage] | [Use case] |
| B | [Advantage] | [Disadvantage] | [Use case] |

**If Not Specified:** [Default choice + rationale]

---

## 🟡 HIGH-LEVERAGE (Affects Implementation)

### 2. **[DECISION POINT]**
[Same format]

---

## 🟢 NICE-TO-HAVE (Edge Cases)

### 3. **[DECISION POINT]**
[Same format]
```

---

## 🔄 Итеративное уточнение

### Первый проход (3–5 вопросов)
Фокус на **blocking decisions**. Не продолжать без ответов.

### Второй проход (после первой реализации)
Когда проявились паттерны:
- «Эта фича подразумевает [X]. Обрабатываем [edge case] сейчас или откладываем?»
- «Мы используем [Pattern A]. Должна ли [Feature B] следовать тому же паттерну?»

### Третий проход (optimization)
Когда функциональность работает:
- «Узкое место в [X]. Оптимизируем сейчас или пока допустимо?»
- «Рефакторим [Y] для maintainability или ship as-is?»

---

## 🎭 Пример: полная генерация вопросов

```
USER REQUEST: "Build an Instagram clone"

STEP 1: Parse
├── Domain: Social Media
├── Features: Photo sharing, engagement (likes/comments), user profiles
├── Implied: Feed, following, authentication
└── Scale: Potentially high (social apps go viral)

STEP 2: Decision Points
├── Blocking: Storage strategy, authentication method, feed type
├── High-leverage: Real-time notifications, data model complexity
└── Deferable: Analytics, advanced search, reels/video

STEP 3: Generate Questions (Priority)

P0 (Blocking):
1. Storage Strategy → Affects architecture, cost, speed
2. Feed Algorithm → Affects database queries, complexity
3. Auth Method → Affects dev time, UX, security

P1 (High-leverage):
4. Real-time Notifications → WebSocket vs polling
5. Media Processing → Client-side vs server-side optimization

P2 (Deferable):
6. Story/Reels → Major feature creep, defer to v2
7. DM/Chat → Separate subsystem, defer to v2

STEP 4: Format Output
```

---

## 📊 Пример сгенерированного вывода

```
Based on your Instagram clone request:

## 🔴 CRITICAL DECISIONS (Cannot Proceed Without Answers)

### 1. **Photo Storage Strategy**

**Question:** Where will user photos be stored and served?

**Why This Matters:**
- Affects: Monthly hosting costs, page load speed, CDN complexity
- High-volume social apps: 1000 users × 10 photos × 2MB = 20GB storage

**Options:**
| Option | Cost | Speed | Complexity | Best For |
|--------|------|-------|------------|----------|
| **Cloudinary** | $89/mo (25GB) | Fast (CDN) | Low | MVP, rapid launch |
| **AWS S3 + CloudFront** | $0.023/GB | Fast (CDN) | Medium | Production, cost-optimized |
| **Supabase Storage** | Free tier 1GB | Medium | Low | Small scale, simple |
| **Local Storage** | Server cost | Slow | Low | Development only |

**If Not Specified:** Cloudinary (balanced for MVP)

---

### 2. **Feed Algorithm Choice**

**Question:** How should the main feed work?

**Why This Matters:**
- Affects: Database query complexity, caching strategy, development time
- Algorithmic feeds require ML infrastructure, ranking signals

**Options:**
| Option | Complexity | Database Impact | Best For |
|--------|------------|-----------------|----------|
| **Chronological** | Low | Simple query | Early stage, transparency |
| **Follow-Only** | Medium | JOIN with pagination | Most social apps |
| **Algorithmic** | High | Pre-computed feed tables | Instagram competitor |

**If Not Specified:** Follow-Only (balanced for social app)

---

### 3. **Authentication Approach**

**Question:** How do users sign up and login?

**Why This Matters:**
- Affects: Development time (2-5 hours), security posture, UX friction

**Options:**
| Option | Dev Time | Security | UX | Best For |
|--------|----------|----------|-----|----------|
| **Email/Password** | 4-5 hrs | High (if 2FA) | Medium | Full control needed |
| **Social Only** | 1-2 hrs | Provider-dependent | Smooth | B2C, rapid launch |
| **Magic Link** | 2-3 hrs | Medium | Very smooth | Security-focused |
| **Clerk/Auth0** | 1 hr | High | Smooth | Fastest to market |

**If Not Specified:** Clerk (fastest for MVP)

---

## 🟡 HIGH-LEVERAGE (Affects Architecture)

### 4. **Real-time Notifications**

**Question:** Do users need instant notifications for likes/comments?

**Why This Matters:**
- WebSocket adds infrastructure complexity (Redis pub/sub for scaling)
- Polling is simpler but higher latency

**Options:**
| Option | Complexity | Scale Cost | Best For |
|--------|------------|------------|----------|
| **WebSocket + Redis** | High | $10+/mo | >1000 concurrent users |
| **Polling (30s)** | Low | DB queries | <1000 users |
| **No Real-time** | None | None | MVP, validate first |

**If Not Specified:** Polling for MVP (defer WebSocket until validated)

---

## 🟢 NICE-TO-HAVE (Defer to v2)

### 5. **Video/Reels Support**
- Major complexity (video processing, streaming infrastructure)
- Recommendation: Launch with photos only, add video after validation

### 6. **Direct Messaging**
- Separate subsystem (chat infrastructure different from feed)
- Recommendation: Use Pusher/Stream for real-time or defer entirely

---

## 📋 Summary

| Decision | Recommendation | If Changed |
|----------|----------------|------------|
| Storage | Cloudinary | +3 hrs setup |
| Feed | Follow-only | +2 hrs query optimization |
| Auth | Clerk | -3 hrs dev time |
| Real-time | Polling | +5 hrs WebSocket setup |
| Video | Defer to v2 | N/A |
| DM | Defer to v2 | N/A |

**Total Estimated MVP Time:** 15-20 hours with recommendations above
```

---

## 🎯 Кратко: принципы

1. **Каждый вопрос = архитектурное решение** — не сбор данных ради данных
2. **Показывать trade-offs** — пользователь видит последствия
3. **Сначала blocking decisions** — без них не идти дальше
4. **Давать defaults** — если нет ответа, всё равно можно двигаться осознанно
5. **Учитывать domain** — вопросы для ecommerce ≠ auth ≠ real-time
6. **Итеративно** — новые вопросы по мере появления паттернов в реализации
