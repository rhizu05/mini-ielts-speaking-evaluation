<template>
  <div class="app-shell min-h-screen">
    <section v-if="!token" class="auth-screen min-h-screen grid lg:grid-cols-[1.05fr_0.95fr]">
      <div class="auth-intro hidden lg:flex flex-col justify-between p-12 xl:p-20">
        <div class="brand-mark">
          <span class="brand-dot"></span>
          IELTS / speaking lab
        </div>
        <div>
          <p class="eyebrow mb-5">Practice with purpose</p>
          <h1 class="display-title max-w-xl">Find your voice.<br /><em>Build your band.</em></h1>
          <p class="mt-7 max-w-md text-base leading-7 text-(--muted)">
            Simple, focused speaking practice with feedback you can actually use to improve.
          </p>
        </div>
        <p class="text-xs uppercase tracking-[0.18em] text-(--muted)">Mini evaluation workspace · 2026</p>
      </div>

      <div class="auth-panel flex items-center px-6 py-10 sm:px-12">
        <div class="w-full max-w-md mx-auto">
          <div class="lg:hidden brand-mark mb-16"><span class="brand-dot"></span> IELTS / speaking lab</div>
          <p class="eyebrow mb-4">Your practice space</p>
          <h2 class="section-title">{{ authMode === "login" ? "Welcome back." : "Start practicing." }}</h2>
          <p class="mt-3 text-sm leading-6 text-(--muted)">
            {{ authMode === "login" ? "Sign in to continue your practice." : "Create an account to save all your practice results." }}
          </p>

          <form class="mt-9 space-y-5" @submit.prevent="auth">
            <label v-if="authMode === 'register'" class="field-label">
              Name
              <input v-model="authForm.name" class="field-input" type="text" autocomplete="name" required />
            </label>
            <label class="field-label">
              Email
              <input v-model="authForm.email" class="field-input" type="email" autocomplete="email" required />
            </label>
            <label class="field-label">
              Password
              <input v-model="authForm.password" class="field-input" type="password" autocomplete="current-password" required />
            </label>
            <p v-if="error" class="notice-error">{{ error }}</p>
            <button class="primary-button w-full" type="submit" :disabled="authLoading">
              {{ authLoading ? "Processing..." : (authMode === "login" ? "Sign in to workspace" : "Create account") }}
              <span aria-hidden="true">→</span>
            </button>
          </form>

          <button class="switch-button mt-7" type="button" @click="toggleAuthMode">
            {{ authMode === "login" ? "Don't have an account? Sign up" : "Already have an account? Sign in" }}
          </button>
        </div>
      </div>
    </section>

    <section v-else class="workspace min-h-screen lg:flex">
      <aside class="sidebar flex flex-col px-6 py-6 lg:min-h-screen lg:w-72 lg:px-8 lg:py-8">
        <div class="brand-mark"><span class="brand-dot"></span> IELTS / lab</div>
        <div class="mt-12 hidden lg:block">
          <p class="eyebrow mb-4">Workspace</p>
          <nav class="space-y-1">
            <a class="nav-item nav-item-active" href="#overview"><span>Overview</span><span>01</span></a>
            <a class="nav-item" href="#practice"><span>New practice</span><span>02</span></a>
            <a class="nav-item" href="#history"><span>History</span><span>03</span></a>
          </nav>
        </div>
        <div class="mt-auto hidden lg:block">
          <div class="user-card">
            <div class="avatar">{{ user?.name?.charAt(0)?.toUpperCase() }}</div>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold">{{ user?.name }}</p>
              <p class="truncate text-xs text-(--muted)">{{ user?.email }}</p>
            </div>
          </div>
          <button class="logout-button mt-5" type="button" @click="logout">Sign out <span>↗</span></button>
        </div>
      </aside>

      <main class="flex-1 px-5 py-6 sm:px-8 lg:px-12 lg:py-10 xl:px-16">
        <header class="mb-10 flex items-start justify-between gap-4" id="overview">
          <div>
            <p class="eyebrow mb-3">{{ todayLabel }}</p>
            <h1 class="display-title text-4xl sm:text-5xl">Good to see you,<br /><em>{{ firstName }}.</em></h1>
          </div>
          <button class="mobile-logout lg:hidden" type="button" @click="logout">Sign out</button>
        </header>

        <div class="stats-grid mb-10">
          <div class="stat-card stat-card-accent">
            <p class="eyebrow">Total attempts</p>
            <p class="stat-number">{{ attempts.length }}</p>
            <p class="stat-caption">Keep showing up.</p>
          </div>
          <div class="stat-card">
            <p class="eyebrow">Latest band</p>
            <p class="stat-number">{{ latestScore }}</p>
            <p class="stat-caption">Your latest signal.</p>
          </div>
          <div class="stat-card hidden sm:block">
            <p class="eyebrow">Questions ready</p>
            <p class="stat-number">{{ questions.length }}</p>
            <p class="stat-caption">Across 3 IELTS parts.</p>
          </div>
        </div>

        <div class="content-grid">
          <section id="practice" class="practice-card">
            <div class="mb-8 flex items-start justify-between gap-4">
              <div>
                <p class="eyebrow mb-3">New session</p>
                <h2 class="section-title">Practice prompt</h2>
              </div>
              <span class="step-label">01 / 01</span>
            </div>
            <form @submit.prevent="submit">
              <label class="field-label">
                Choose a question
                <select v-model="selectedQuestionId" class="field-input" required>
                  <option value="" disabled>Select a prompt</option>
                  <option v-for="q in questions" :key="q.id" :value="q.id">Part {{ q.part }} · {{ q.topic }}</option>
                </select>
              </label>
              <div v-if="selectedQuestion" class="prompt-box mt-5">
                <span class="prompt-number">Prompt</span>
                <p>{{ selectedQuestion.question_text }}</p>
              </div>
              <label class="field-label mt-6">
                Your answer
                <textarea v-model="answer" class="field-input answer-input" rows="7" placeholder="Write your speaking answer here..." required></textarea>
              </label>
              <div class="mt-3 flex items-center justify-between text-xs text-(--muted)">
                <span>Minimum 20 characters</span><span>{{ answer.length }} / 2000</span>
              </div>
              <p v-if="error" class="notice-error mt-5">{{ error }}</p>
              <button class="primary-button mt-7" type="submit" :disabled="loading">
                {{ loading ? "Evaluating..." : "Evaluate answer" }} <span aria-hidden="true">→</span>
              </button>
            </form>
          </section>

          <section id="history" class="history-section">
            <div class="mb-5 flex items-end justify-between">
              <div><p class="eyebrow mb-3">Your progress</p><h2 class="section-title">Recent attempts</h2></div>
              <span class="text-xs text-(--muted)">{{ attempts.length }} total</span>
            </div>
            <div v-if="attempts.length === 0" class="empty-state">Your first evaluation will appear here.</div>
            <div v-else class="attempt-list">
              <button v-for="attempt in attempts" :key="attempt.id" class="attempt-row" :class="{ 'attempt-row-active': selectedAttempt?.id === attempt.id }" type="button" @click="selectedAttempt = attempt">
                <span class="attempt-index">{{ String(attempt.id).padStart(2, '0') }}</span>
                <span class="min-w-0 flex-1 text-left"><strong>Part {{ attempt.question?.part }} · {{ attempt.question?.topic }}</strong><small>{{ formatDate(attempt.created_at) }}</small></span>
                <span class="attempt-score">{{ attempt.band_score ?? "—" }}</span><span class="arrow">↗</span>
              </button>
            </div>

            <div v-if="selectedAttempt" class="feedback-panel mt-8">
              <div class="flex items-start justify-between gap-4 border-b border-(--line) pb-6">
                <div><p class="eyebrow mb-3">Evaluation detail</p><h2 class="section-title">Your feedback</h2></div>
                <div class="score-stamp"><span>Band</span><strong>{{ selectedAttempt.band_score ?? "—" }}</strong></div>
              </div>
              <div class="mt-6"><p class="eyebrow mb-2">Prompt</p><p class="leading-6 text-(--ink)">{{ selectedAttempt.question?.question_text }}</p></div>
              <div class="mt-6"><p class="eyebrow mb-2">Your answer</p><p class="answer-quote">“{{ selectedAttempt.answer_text }}”</p></div>
              <div class="feedback-columns mt-7">
                <div><p class="eyebrow mb-3">Strengths</p><ul class="feedback-list"><li v-for="(item, index) in selectedAttempt.strengths || []" :key="index">{{ item }}</li></ul></div>
                <div><p class="eyebrow mb-3">Improve next</p><ul class="feedback-list feedback-list-improve"><li v-for="(item, index) in selectedAttempt.improvements || []" :key="index">{{ item }}</li></ul></div>
              </div>
              <div class="gemini-note mt-7"><p class="eyebrow mb-2">Examiner note</p><p class="leading-6">{{ selectedAttempt.raw_feedback || "No additional note available." }}</p></div>
            </div>
          </section>
        </div>
      </main>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";

const token = ref(localStorage.getItem("token") || "");
const user = ref(null);
const questions = ref([]);
const attempts = ref([]);
const selectedAttempt = ref(null);
const selectedQuestionId = ref("");
const answer = ref("");
const error = ref("");
const loading = ref(false);
const authLoading = ref(false);
const authMode = ref("login");
const authForm = ref({ name: "", email: "", password: "" });

const firstName = computed(() => user.value?.name?.split(" ")[0] || "there");
const selectedQuestion = computed(() => questions.value.find((question) => String(question.id) === String(selectedQuestionId.value)));
const latestScore = computed(() => attempts.value[0]?.band_score ?? "—");
const todayLabel = computed(() => new Intl.DateTimeFormat("en-US", { weekday: "long", month: "long", day: "numeric" }).format(new Date()));

function authHeaders() {
  return token.value ? { Authorization: `Bearer ${token.value}` } : {};
}

function toggleAuthMode() {
  error.value = "";
  authMode.value = authMode.value === "login" ? "register" : "login";
}

async function auth() {
  error.value = "";
  authLoading.value = true;
  try {
    const body = { email: authForm.value.email, password: authForm.value.password };
    if (authMode.value === "register") body.name = authForm.value.name;
    const response = await fetch(authMode.value === "login" ? "/api/login" : "/api/register", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(body) });
    const json = await response.json();
    if (!response.ok) { error.value = json.message || "Unable to continue."; return; }
    token.value = json.data.token;
    user.value = json.data.user;
    localStorage.setItem("token", token.value);
    authForm.value = { name: "", email: "", password: "" };
    await loadAttempts();
  } catch { error.value = "Unable to connect to the server."; } finally { authLoading.value = false; }
}

async function logout() {
  try { await fetch("/api/logout", { method: "POST", headers: authHeaders() }); } catch { }
  token.value = "";
  user.value = null;
  attempts.value = [];
  selectedAttempt.value = null;
  localStorage.removeItem("token");
}

async function loadAttempts() {
  if (!token.value) return;
  const response = await fetch("/api/attempts", { headers: authHeaders() });
  if (response.ok) { attempts.value = (await response.json()).data; }
}

async function submit() {
  error.value = "";
  if (!selectedQuestionId.value || answer.value.length < 20) { error.value = "Choose a prompt and write at least 20 characters."; return; }
  loading.value = true;
  try {
    const response = await fetch("/api/speaking/submit", { method: "POST", headers: { "Content-Type": "application/json", ...authHeaders() }, body: JSON.stringify({ question_id: selectedQuestionId.value, answer_text: answer.value }) });
    const json = await response.json();
    if (!response.ok) { error.value = json.message || "Unable to evaluate this answer."; return; }
    answer.value = "";
    await loadAttempts();
    selectedAttempt.value = attempts.value.find((attempt) => attempt.id === json.data.id) || json.data;
  } catch { error.value = "Unable to connect to the server."; } finally { loading.value = false; }
}

function formatDate(value) {
  if (!value) return "Just now";
  return new Intl.DateTimeFormat("en-US", { month: "short", day: "numeric", year: "numeric" }).format(new Date(value));
}

onMounted(async () => {
  const questionsResponse = await fetch("/api/questions");
  if (questionsResponse.ok) questions.value = (await questionsResponse.json()).data;
  if (token.value) {
    const userResponse = await fetch("/api/user", { headers: authHeaders() });
    if (userResponse.ok) { user.value = (await userResponse.json()).data; await loadAttempts(); } else { localStorage.removeItem("token"); token.value = ""; }
  }
});
</script>
