<template>
  <section class="practice-card">
    <div class="mb-8 flex items-start justify-between gap-4">
      <div>
        <p class="eyebrow mb-3">New session</p>
        <h2 class="section-title">Practice prompt</h2>
      </div>
      <span class="step-label">01 / 01</span>
    </div>
    <form @submit.prevent="onSubmit">
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
</template>

<script setup>
import { computed, ref } from "vue";

const props = defineProps({
  questions: { type: Array, required: true },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(["submit"]);

const selectedQuestionId = ref("");
const answer = ref("");
const error = ref("");

const selectedQuestion = computed(() =>
  props.questions.find((question) => String(question.id) === String(selectedQuestionId.value))
);

function onSubmit() {
  error.value = "";
  if (!selectedQuestionId.value || answer.value.length < 20) {
    error.value = "Choose a prompt and write at least 20 characters.";
    return;
  }
  emit("submit", { question_id: selectedQuestionId.value, answer_text: answer.value });
  answer.value = "";
}
</script>
