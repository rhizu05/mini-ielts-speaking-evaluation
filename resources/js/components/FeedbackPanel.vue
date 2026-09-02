<template>
  <div class="feedback-panel mt-8">
    <div class="flex items-start justify-between gap-4 border-b border-(--line) pb-6">
      <div><p class="eyebrow mb-3">Evaluation detail</p><h2 class="section-title">Your feedback</h2></div>
      <div class="score-stamp"><span>Band</span><strong>{{ attempt.band_score ?? "—" }}</strong></div>
    </div>
    <div class="mt-6"><p class="eyebrow mb-2">Prompt</p><p class="leading-6 text-(--ink)">{{ attempt.question?.question_text }}</p></div>
    <div class="mt-6"><p class="eyebrow mb-2">Your answer</p><p class="answer-quote">“{{ attempt.answer_text }}”</p></div>
    <div class="feedback-columns mt-7">
      <div><p class="eyebrow mb-3">Strengths</p><ul class="feedback-list"><li v-for="(item, index) in attempt.strengths || []" :key="index">{{ item }}</li></ul></div>
      <div><p class="eyebrow mb-3">Improve next</p><ul class="feedback-list feedback-list-improve"><li v-for="(item, index) in attempt.improvements || []" :key="index">{{ item }}</li></ul></div>
    </div>
    <div class="gemini-note mt-7"><p class="eyebrow mb-2">Examiner note</p><p class="leading-6">{{ attempt.raw_feedback || "No additional note available." }}</p></div>
  </div>
</template>

<script setup>
defineProps({
  attempt: {
    type: Object,
    required: true,
  },
});
</script>
