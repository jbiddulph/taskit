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
      action_items: actionItems,
      action_items_count: actionItems.length,
      participants_mentioned: parsed.participants_mentioned ?? [],
      follow_ups: parsed.follow_ups ?? [],
      processed_at: new Date().toISOString(),
    },
  },
];
