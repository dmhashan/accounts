<template>
  <section class="app-page-frame">
    <AppPageHeader>
      <template #title-slot>
        <div class="flex items-center gap-2">
          <span>AI Assistant</span>
          <span class="px-2 py-0.5 text-xs font-bold bg-primary-100 text-primary-700 dark:bg-primary-950/40 dark:text-primary-400 rounded-full border border-primary-200/50 dark:border-primary-800/30 shrink-0 leading-none">Beta</span>
        </div>
      </template>
      <template #cta-slot>
        <div class="flex items-center gap-2">
          <!-- Connection Status Badge -->
          <div 
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold"
            :class="geminiConnected ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/30' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/30'"
          >
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="geminiConnected ? 'bg-emerald-400' : 'bg-amber-400'" />
              <span class="relative inline-flex rounded-full h-2 w-2" :class="geminiConnected ? 'bg-emerald-500' : 'bg-amber-500'" />
            </span>
            <span>{{ geminiConnected ? 'Gemini AI Connected' : 'Local Rule Engine' }}</span>
          </div>
          
          <button
            type="button"
            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-secondary-300 bg-white px-3 text-sm font-semibold text-secondary-700 transition-colors hover:bg-secondary-50 dark:border-secondary-700 dark:bg-secondary-900 dark:text-secondary-300 dark:hover:bg-secondary-800"
            @click="clearChat"
          >
            <Trash2 class="h-4 w-4" />
            <span>Clear Chat</span>
          </button>
        </div>
      </template>
    </AppPageHeader>

    <!-- Warning alert if Gemini is not connected -->
    <div 
      v-if="!geminiConnected"
      class="mb-4 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 flex gap-3 items-start text-sm text-amber-800 dark:text-amber-200 shadow-sm"
    >
      <AlertTriangle class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
      <div>
        <h4 class="font-bold text-amber-900 dark:text-amber-300 mb-0.5">
          Gemini AI Not Connected
        </h4>
        <p class="leading-relaxed opacity-90">
          The application could not establish a connection to the Gemini API. The assistant is currently running in fallback mode using the local rule-based query engine. AI-powered conversations will be unavailable, but you can still run direct queries on database metrics.
        </p>
      </div>
    </div>

    <div class="min-h-0 flex flex-1 flex-col p-4 md:p-6 bg-secondary-50/50 dark:bg-secondary-950/30 rounded-3xl border border-secondary-200/50 dark:border-secondary-800/30 overflow-hidden">
      <!-- Chat Messages Container -->
      <div 
        ref="messageContainer"
        class="flex-1 overflow-y-auto space-y-4 pr-2 mb-4 scroll-smooth"
      >
        <!-- Welcome Message -->
        <div class="flex gap-3 max-w-[85%]">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-primary-500 to-indigo-600 flex items-center justify-center shrink-0 shadow-md">
            <Sparkles class="w-5 h-5 text-white" />
          </div>
          <div class="app-surface rounded-2xl rounded-tl-none p-4 border border-secondary-200/60 dark:border-secondary-800/60 shadow-sm">
            <h3 class="font-bold text-secondary-900 dark:text-white mb-1 flex items-center gap-1.5">
              <span>AI Assistant</span>
            </h3>
            <p class="text-sm text-secondary-700 dark:text-secondary-300 leading-relaxed">
              Hello! I am your AI assistant. I can query our live gym database to retrieve financial summaries, attendance statistics, popular subscription plans, and loyalty metrics.
            </p>
            <p class="text-sm text-secondary-700 dark:text-secondary-300 mt-2 leading-relaxed">
              Try asking me:
            </p>
            <ul class="mt-2 text-xs space-y-1 text-secondary-600 dark:text-secondary-400 list-disc pl-4">
              <li>What is the predicted income for next month?</li>
              <li>Who is the best member and what is the reason?</li>
              <li>Summarize active membership counts and plans.</li>
            </ul>
          </div>
        </div>

        <!-- Chat Feed -->
        <div 
          v-for="(msg, idx) in messages" 
          :key="idx" 
          class="flex gap-3 max-w-[85%]"
          :class="msg.role === 'user' ? 'ml-auto flex-row-reverse' : ''"
        >
          <!-- Avatar -->
          <div 
            class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-md"
            :class="msg.role === 'user' 
              ? 'bg-secondary-200 dark:bg-secondary-800' 
              : 'bg-gradient-to-tr from-primary-500 to-indigo-600'"
          >
            <User v-if="msg.role === 'user'" class="w-5 h-5 text-secondary-600 dark:text-secondary-300" />
            <Sparkles v-else class="w-5 h-5 text-white" />
          </div>

          <!-- Bubble -->
          <div 
            class="rounded-2xl p-4 shadow-sm border text-sm leading-relaxed"
            :class="msg.role === 'user' 
              ? 'bg-primary-600 text-white border-primary-700 rounded-tr-none' 
              : 'app-surface text-secondary-800 dark:text-secondary-200 border-secondary-200/60 dark:border-secondary-800/60 rounded-tl-none'"
          >
            <div v-if="msg.role === 'user'" class="whitespace-pre-wrap font-medium">
              {{ msg.text }}
            </div>
            <div 
              v-else 
              class="chatbot-content markdown-body overflow-x-auto" 
              v-html="renderMarkdown(msg.text)"
            />
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex gap-3 max-w-[85%]">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-primary-500 to-indigo-600 flex items-center justify-center shrink-0 shadow-md">
            <Sparkles class="w-5 h-5 text-white" />
          </div>
          <div class="app-surface rounded-2xl rounded-tl-none p-4 border border-secondary-200/60 dark:border-secondary-800/60 flex items-center gap-1.5 shadow-sm">
            <span class="w-2 h-2 bg-secondary-400 dark:bg-secondary-600 rounded-full animate-bounce" style="animation-delay: 0ms" />
            <span class="w-2 h-2 bg-secondary-400 dark:bg-secondary-600 rounded-full animate-bounce" style="animation-delay: 150ms" />
            <span class="w-2 h-2 bg-secondary-400 dark:bg-secondary-600 rounded-full animate-bounce" style="animation-delay: 300ms" />
          </div>
        </div>
      </div>

      <!-- Quick Actions / Suggestions -->
      <div v-if="messages.length === 0" class="flex flex-wrap gap-2 mb-3">
        <button 
          v-for="sug in suggestions" 
          :key="sug"
          type="button"
          class="px-3.5 py-1.5 bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 rounded-full text-xs font-semibold text-secondary-700 dark:text-secondary-300 hover:border-primary-500 hover:text-primary-600 transition-all cursor-pointer shadow-sm"
          @click="sendSuggestion(sug)"
        >
          {{ sug }}
        </button>
      </div>

      <!-- Message Input Box -->
      <form class="flex items-center gap-2 border border-secondary-200 dark:border-secondary-800 bg-white dark:bg-secondary-900 rounded-2xl p-1.5 shadow-sm shrink-0" @submit.prevent="sendMessage">
        <input 
          v-model="inputMessage"
          type="text"
          placeholder="Ask a question about financial forecasts or gym members..."
          class="flex-1 bg-transparent px-3 py-2 text-sm text-secondary-900 dark:text-white outline-none placeholder-secondary-400 dark:placeholder-secondary-500"
          :disabled="loading"
        />
        <button 
          type="submit"
          class="p-2.5 bg-primary-600 hover:bg-primary-500 disabled:bg-secondary-200 disabled:dark:bg-secondary-800 text-white rounded-xl transition-colors cursor-pointer shrink-0"
          :disabled="loading || !inputMessage.trim()"
        >
          <Send class="w-4 h-4" />
        </button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { apiRequest } from '../composables/useApiClient';
import { Sparkles, User, Send, Trash2, AlertTriangle } from 'lucide-vue-next';
import AppPageHeader from '../components/AppPageHeader.vue';

const messages = ref([]);
const inputMessage = ref('');
const loading = ref(false);
const geminiConnected = ref(false);
const messageContainer = ref(null);

const suggestions = [
  'What is the predicted income for next month?',
  'Who is the best member and reason it?',
  'Show the monthly payment history summary',
  'What plans are most popular among members?'
];

onMounted(() => {
  // Load initial check or config state
  checkConnection();
});

async function checkConnection() {
  try {
    const res = await apiRequest('/api/chatbot/message', { method: 'POST', data: { message: 'hello' } });
    geminiConnected.value = res.gemini_connected ?? false;
  } catch (err) {
    console.error('Failed to query chatbot state', err);
  }
}

async function sendMessage() {
  const text = inputMessage.value.trim();
  if (!text || loading.value) return;

  messages.value.push({ role: 'user', text });
  inputMessage.value = '';
  loading.value = true;
  
  await scrollToBottom();

  try {
    const res = await apiRequest('/api/chatbot/message', { method: 'POST', data: { message: text } });
    messages.value.push({ 
      role: 'bot', 
      text: res.answer 
    });
    geminiConnected.value = res.gemini_connected ?? false;
  } catch (err) {
    console.error(err);
    messages.value.push({ 
      role: 'bot', 
      text: 'Sorry, I encountered an error communicating with the chat API. Please verify your connection.' 
    });
  } finally {
    loading.value = false;
    await scrollToBottom();
  }
}

function sendSuggestion(sug) {
  inputMessage.value = sug;
  sendMessage();
}

function clearChat() {
  messages.value = [];
}

async function scrollToBottom() {
  await nextTick();
  if (messageContainer.value) {
    messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
  }
}

/**
 * Clean & Beautiful custom Markdown Parser.
 */
function renderMarkdown(content) {
  if (!content) return '';
  
  // Basic HTML sanitization to avoid raw scripts injection
  let html = content
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
  
  // Bold: **text**
  html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-secondary-900 dark:text-white">$1</strong>');
  
  // Italic: *text*
  html = html.replace(/\*(.*?)\*/g, '<em class="italic">$1</em>');
  
  // Headers
  html = html.replace(/^### (.*?)$/gm, '<h4 class="text-md font-bold mt-4 mb-1 text-primary-600 dark:text-primary-400">$1</h4>');
  html = html.replace(/^## (.*?)$/gm, '<h3 class="text-lg font-bold mt-5 mb-2 text-indigo-600 dark:text-indigo-400">$1</h3>');

  // Bullet Points
  html = html.replace(/^\s*[-*]\s+(.*?)$/gm, '<li class="ml-4 list-disc my-1 text-secondary-700 dark:text-secondary-300">$1</li>');

  // Parse Markdown Tables
  const lines = html.split('\n');
  let inTable = false;
  let tableHtml = '';
  const finalLines = [];
  
  for (const line of lines) {
    if (line.trim().startsWith('|')) {
      const cells = line.split('|').map(c => c.trim()).filter((c, i, a) => i > 0 && i < a.length - 1);
      if (!inTable) {
        inTable = true;
        tableHtml = '<div class="overflow-x-auto my-4 border border-secondary-200 dark:border-secondary-800 rounded-xl shadow-sm"><table class="min-w-full divide-y divide-secondary-200 dark:divide-secondary-800 text-xs"><thead class="bg-secondary-50 dark:bg-secondary-800/40"><tr>';
        for (const cell of cells) {
          tableHtml += `<th class="px-4 py-2.5 text-left font-bold text-secondary-600 dark:text-secondary-400 uppercase tracking-wider">${cell}</th>`;
        }
        tableHtml += '</tr></thead><tbody class="divide-y divide-secondary-150 dark:divide-secondary-800 bg-white/50 dark:bg-secondary-900/30">';
      } else {
        if (line.includes('---')) {
          continue; // skip divider lines
        }
        tableHtml += '<tr class="hover:bg-secondary-50/50 dark:hover:bg-secondary-800/20">';
        for (const cell of cells) {
          tableHtml += `<td class="px-4 py-2.5 text-secondary-700 dark:text-secondary-300">${cell}</td>`;
        }
        tableHtml += '</tr>';
      }
    } else {
      if (inTable) {
        inTable = false;
        tableHtml += '</tbody></table></div>';
        finalLines.push(tableHtml);
        tableHtml = '';
      }
      finalLines.push(line);
    }
  }
  
  if (inTable) {
    tableHtml += '</tbody></table></div>';
    finalLines.push(tableHtml);
  }
  
  html = finalLines.join('\n');
  
  // Convert newlines to HTML breaks
  html = html.replace(/\n/g, '<br>');
  
  // Remove breaks in table/list components
  html = html.replace(/<\/tr><br>/g, '</tr>');
  html = html.replace(/<\/thead><br>/g, '</thead>');
  html = html.replace(/<\/tbody><br>/g, '</tbody>');
  html = html.replace(/<\/table><br><\/div>/g, '</table></div>');
  html = html.replace(/<li class="ml-4 list-disc my-1 text-secondary-700 dark:text-secondary-300">(.*?)<\/li><br>/g, '<li class="ml-4 list-disc my-1 text-secondary-700 dark:text-secondary-300">$1</li>');
  
  return html;
}
</script>

<style>
.chatbot-content {
  line-height: 1.6;
}
.chatbot-content table {
  border-collapse: collapse;
}
</style>
