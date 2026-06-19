// Reference implementation for the "Structure Meeting Notes" n8n Code node.
// Keep in sync with zaptask-meeting-notes-workflow.json

const context = $('Prepare Meeting Context').first().json;
const ai = $input.first().json;

let parsed = ai.message?.content ?? ai.content ?? ai;

if (typeof parsed === 'string') {
  try {
    parsed = JSON.parse(parsed);
  } catch {
    parsed = {
      summary: parsed,
      suggested_project_name: null,
      key_decisions: [],
      action_items: [],
      participants_mentioned: [],
      follow_ups: [],
    };
  }
}

const availableProjects = Array.isArray(context.available_projects) ? context.available_projects : [];
const transcript = String(context.transcript || '');

const STOP_WORDS = new Set(['a', 'an', 'the', 'my', 'our', 'for', 'in', 'on', 'to', 'and', 'or', 'of', 'project', 'projects']);

const normalizeText = (value) =>
  String(value || '')
    .toLowerCase()
    .replace(/[^a-z0-9\s]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

const textTokens = (value) =>
  normalizeText(value)
    .split(' ')
    .filter((token) => token.length > 1 && !STOP_WORDS.has(token));

const tokenOverlapScore = (query, candidate) => {
  const queryTokens = textTokens(query);
  const candidateTokens = new Set(textTokens(candidate));
  if (!queryTokens.length || !candidateTokens.size) return 0;
  const overlap = queryTokens.filter((token) => candidateTokens.has(token)).length;
  return overlap / Math.max(queryTokens.length, candidateTokens.size);
};

const inferStatusFromText = (text) => {
  const normalized = normalizeText(text);
  if (!normalized) return null;

  if (/\b(in[\s-]?progress|progress\s+column|being\s+worked|working\s+on|under[\s-]?way|started|active|wip|doing|current\s+sprint)\b/.test(normalized)) {
    return 'in-progress';
  }
  if (/\b(q\s*&?\s*a|qa[\s-]?testing|testing\s+column|in\s+review|for\s+review|needs?\s+review|quality\s+assurance|testing)\b/.test(normalized)) {
    return 'qa-testing';
  }
  if (/\b(completed?|finished|shipped|closed|done\s+column)\b/.test(normalized)) {
    return 'done';
  }
  if (/\b(to[\s-]?do|todo\s+column|backlog|not\s+started|new\s+column|pending|icebox)\b/.test(normalized)) {
    return 'todo';
  }
  return null;
};

const normalizeStatus = (status, fallbackText = '') => {
  return inferStatusFromText(status) || inferStatusFromText(fallbackText) || 'todo';
};

const matchProjectName = (spoken) => {
  if (!spoken || !availableProjects.length) return null;

  const normalizedQuery = normalizeText(spoken);
  const exact = availableProjects.find((project) => normalizeText(project.name) === normalizedQuery);
  if (exact) return exact.name;

  const partial = availableProjects.find((project) => {
    const normalizedName = normalizeText(project.name);
    return normalizedName.includes(normalizedQuery) || normalizedQuery.includes(normalizedName);
  });
  if (partial) return partial.name;

  let best = null;
  for (const project of availableProjects) {
    const score = tokenOverlapScore(spoken, project.name);
    if (score >= 0.35 && (!best || score > best.score)) {
      best = { name: project.name, score };
    }
  }
  return best?.name ?? null;
};

const findProjectInTranscript = () => {
  if (!transcript || !availableProjects.length) return null;

  const normalizedTranscript = normalizeText(transcript);
  let best = null;

  for (const project of availableProjects) {
    const normalizedName = normalizeText(project.name);
    if (normalizedName && normalizedTranscript.includes(normalizedName)) {
      const score = normalizedName.length;
      if (!best || score > best.score) best = { name: project.name, score };
      continue;
    }

    const score = tokenOverlapScore(project.name, transcript);
    if (score >= 0.5 && (!best || score > best.score)) {
      best = { name: project.name, score };
    }
  }

  return best?.name ?? null;
};

const inferGlobalStatus = () => {
  const placementPatterns = [
    /(?:put|place|add|move|stick)\s+(?:them|these|it|all|both)?\s*(?:in|into|on|under)\s+(?:the\s+)?([^.,;]{3,60})/gi,
    /(?:in|into|on)\s+(?:the\s+)?([^.,;]{3,40}?)\s+column/gi,
  ];

  for (const pattern of placementPatterns) {
    let match;
    while ((match = pattern.exec(transcript)) !== null) {
      const status = inferStatusFromText(match[1]);
      if (status && status !== 'todo') return status;
    }
  }

  return inferStatusFromText(transcript);
};

const WORD_NUMBERS = {
  one: 1,
  two: 2,
  three: 3,
  four: 4,
  five: 5,
  six: 6,
  seven: 7,
  eight: 8,
  nine: 9,
  ten: 10,
  eleven: 11,
  twelve: 12,
};

const ITEM_WORD_PATTERN = 'todos?|tasks?|notes?|action items?|reminders?|items?|things to do';

const inferItemTypeLabel = (text) => {
  const normalized = normalizeText(text);
  if (/\bnotes?\b/.test(normalized)) return 'Note';
  if (/\btasks?\b/.test(normalized)) return 'Task';
  return 'Todo';
};

const inferRequestedItemCount = (text) => {
  const normalized = normalizeText(text);
  if (!normalized) return 0;

  let maxCount = 0;

  const patterns = [
    new RegExp(
      `(?:create|add|make|need|want|generate|give me|i need|i want|lets create|let us create)\\s+(\\d{1,2})\\s+(?:${ITEM_WORD_PATTERN})`,
      'gi',
    ),
    new RegExp(`(\\d{1,2})\\s+(?:${ITEM_WORD_PATTERN})(?:\\s+to)?(?:\\s+create)?`, 'gi'),
    new RegExp(`(?:create|add|make)\\s+(\\d{1,2})(?:\\s+(?:new|more))?`, 'gi'),
  ];

  for (const pattern of patterns) {
    let match;
    while ((match = pattern.exec(normalized)) !== null) {
      const count = parseInt(match[1], 10);
      if (!Number.isNaN(count) && count > maxCount && count <= 20) {
        maxCount = count;
      }
    }
  }

  const wordPattern = new RegExp(
    `(?:create|add|make|need|want|generate|give me|i need|i want)\\s+(one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve)\\s+(?:${ITEM_WORD_PATTERN})`,
    'gi',
  );

  let wordMatch;
  while ((wordMatch = wordPattern.exec(normalized)) !== null) {
    const count = WORD_NUMBERS[wordMatch[1].toLowerCase()] || 0;
    if (count > maxCount) {
      maxCount = count;
    }
  }

  return maxCount;
};

const padActionItemsToCount = (items, count, label, defaults) => {
  const result = [...items];

  while (result.length < count) {
    result.push({
      title: `${label} ${result.length + 1}`,
      assignee: defaults.assignee ?? null,
      priority: defaults.priority ?? 'Medium',
      status: defaults.status ?? 'todo',
      due_date: defaults.due_date ?? null,
      project_name: defaults.project_name ?? null,
      notes: defaults.notes ?? 'Requested in recording — edit title before approving.',
    });
  }

  return result;
};

const globalStatus = inferGlobalStatus();
const transcriptProject = findProjectInTranscript();
let suggestedProject =
  matchProjectName(parsed.suggested_project_name) ||
  transcriptProject ||
  parsed.suggested_project_name ||
  null;

const actionItems = (Array.isArray(parsed.action_items) ? parsed.action_items : []).map((item) => {
  const projectName =
    matchProjectName(item.project_name) ||
    matchProjectName(suggestedProject) ||
    transcriptProject ||
    item.project_name ||
    suggestedProject ||
    null;

  if (!suggestedProject && projectName) {
    suggestedProject = projectName;
  }

  const status =
    normalizeStatus(item.status, `${item.notes || ''} ${item.title || ''}`) !== 'todo'
      ? normalizeStatus(item.status, `${item.notes || ''} ${item.title || ''}`)
      : globalStatus || normalizeStatus(item.status, `${item.notes || ''} ${item.title || ''}`);

  return {
    title: item.title,
    assignee: item.assignee ?? null,
    priority: item.priority ?? 'Medium',
    status,
    due_date: item.due_date ?? null,
    project_name: projectName,
    notes: item.notes ?? null,
  };
});

const requestedCount = inferRequestedItemCount(transcript);
const itemLabel = inferItemTypeLabel(transcript);
const paddedActionItems =
  requestedCount > 0 && actionItems.length < requestedCount
    ? padActionItemsToCount(actionItems, requestedCount, itemLabel, {
        priority: 'Medium',
        status: globalStatus || 'todo',
        project_name: suggestedProject,
        notes: 'Requested in recording — edit title before approving.',
      })
    : actionItems;

return [
  {
    json: {
      transcript,
      user_id: context.user_id,
      user_name: context.user_name,
      user_email: context.user_email,
      company_id: context.company_id,
      duration_seconds: context.duration_seconds,
      stopped_reason: context.stopped_reason,
      recorded_at: context.recorded_at,
      meeting_summary: parsed.summary ?? '',
      suggested_project_name: suggestedProject,
      key_decisions: parsed.key_decisions ?? [],
      action_items: paddedActionItems,
      action_items_count: paddedActionItems.length,
      requested_item_count: requestedCount,
      participants_mentioned: parsed.participants_mentioned ?? [],
      follow_ups: parsed.follow_ups ?? [],
      processed_at: new Date().toISOString(),
    },
  },
];
