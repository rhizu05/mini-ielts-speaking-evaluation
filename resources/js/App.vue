<template>
  <div class="app-shell min-h-screen">
    <section v-if="!isAuthed && !guestMode" class="auth-screen min-h-screen grid lg:grid-cols-[1.05fr_0.95fr]">
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

          <div class="auth-divider"><span>or</span></div>
          <button class="ghost-button w-full" type="button" @click="enterGuest">Continue as guest</button>

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
            <a v-if="!guestMode" class="nav-item" href="#history"><span>History</span><span>03</span></a>
          </nav>
        </div>
        <div class="mt-auto hidden lg:block">
          <template v-if="guestMode">
            <div class="guest-card">
              <div class="avatar avatar-guest">G</div>
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold">Guest session</p>
                <p class="truncate text-xs text-(--muted)">Progress not saved</p>
              </div>
            </div>
            <button class="logout-button mt-5" type="button" @click="exitGuest">Sign in to save <span>↗</span></button>
          </template>
          <template v-else>
            <div class="user-card">
              <div class="avatar">{{ user?.name?.charAt(0)?.toUpperCase() }}</div>
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ user?.name }}</p>
                <p class="truncate text-xs text-(--muted)">{{ user?.email }}</p>
              </div>
            </div>
            <button class="logout-button mt-5" type="button" @click="logout">Sign out <span>↗</span></button>
          </template>
        </div>
      </aside>

      <main class="flex-1 px-5 py-6 sm:px-8 lg:px-12 lg:py-10 xl:px-16">
        <header class="mb-10 flex items-start justify-between gap-4" id="overview">
          <div>
            <p class="eyebrow mb-3">{{ todayLabel }}</p>
            <h1 class="display-title text-4xl sm:text-5xl">
              {{ guestMode ? "Practice" : "Good to see you," }}<br /><em>{{ guestMode ? "without limits." : firstName + "." }}</em>
            </h1>
          </div>
          <button class="mobile-logout lg:hidden" type="button" @click="guestMode ? exitGuest() : logout()">{{ guestMode ? "Sign in" : "Sign out" }}</button>
        </header>

        <div v-if="!guestMode" class="stats-grid mb-10">
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

        <div class="content-grid" :class="{ 'content-grid-single': guestMode }">
          <PracticeForm :questions="questions" :loading="loading" @submit="submitAnswer" />

          <section v-if="!guestMode" id="history" class="history-section">
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

            <FeedbackPanel v-if="selectedAttempt" :attempt="selectedAttempt" />
          </section>

          <FeedbackPanel v-if="guestMode && selectedAttempt" :attempt="selectedAttempt" />
        </div>
      </main>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import FeedbackPanel from "./components/FeedbackPanel.vue";
import PracticeForm from "./components/PracticeForm.vue";

const token = ref(localStorage.getItem("token") || "");
const guestMode = ref(false);
const user = ref(null);
const questions = ref([]);
const attempts = ref([]);
const selectedAttempt = ref(null);
const error = ref("");
const loading = ref(false);
const authLoading = ref(false);
const authMode = ref("login");
const authForm = ref({ name: "", email: "", password: "" });

const isAuthed = computed(() => !!token.value);
const firstName = computed(() => user.value?.name?.split(" ")[0] || "there");
const latestScore = computed(() => attempts.value[0]?.band_score ?? "—");
const todayLabel = computed(() => new Intl.DateTimeFormat("en-US", { weekday: "long", month: "long", day: "numeric" }).format(new Date()));

function authHeaders() {
  return token.value ? { Authorization: `Bearer ${token.value}` } : {};
}

function toggleAuthMode() {
  error.value = "";
  authMode.value = authMode.value === "login" ? "register" : "login";
}

function enterGuest() {
  error.value = "";
  guestMode.value = true;
  user.value = null;
  selectedAttempt.value = null;
}

function exitGuest() {
  guestMode.value = false;
  selectedAttempt.value = null;
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
    guestMode.value = false;
    localStorage.setItem("token", token.value);
    authForm.value = { name: "", email: "", password: "" };
    await loadAttempts();
  } catch { error.value = "Unable to connect to the server."; } finally { authLoading.value = false; }
}

async function logout() {
  try { await fetch("/api/logout", { method: "POST", headers: authHeaders() }); } catch { }
  token.value = "";
  user.value = null;
  guestMode.value = false;
  attempts.value = [];
  selectedAttempt.value = null;
  localStorage.removeItem("token");
}

async function loadAttempts() {
  if (!token.value) return;
  const response = await fetch("/api/attempts", { headers: authHeaders() });
  if (response.ok) { attempts.value = (await response.json()).data; }
}

async function submitAnswer(payload) {
  loading.value = true;
  try {
    const response = await fetch("/api/speaking/submit", { method: "POST", headers: { "Content-Type": "application/json", ...authHeaders() }, body: JSON.stringify(payload) });
    const json = await response.json();
    if (!response.ok) { error.value = json.message || "Unable to evaluate this answer."; return; }
    selectedAttempt.value = json.data;
    await loadAttempts();
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
