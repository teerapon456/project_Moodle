// Moodle API Types - Quiz

export interface MoodleQuiz {
  id: number;
  course: number;
  coursemodule: number;
  name: string;
  intro?: string;
  introformat?: number;
  introfiles?: unknown[];
  timeopen: number;
  timeclose: number;
  timelimit: number;
  overduehandling?: string;
  graceperiod?: number;
  preferredbehaviour?: string;
  canredoquestions?: number;
  attempts: number;
  attemptonlast: number;
  grademethod: number;
  decimalpoints: number;
  questiondecimalpoints: number;
  reviewattempt?: number;
  reviewcorrectness?: number;
  reviewmaxmarks?: number;
  reviewmarks?: number;
  reviewspecificfeedback?: number;
  reviewgeneralfeedback?: number;
  reviewrightanswer?: number;
  reviewoverallfeedback?: number;
  questionsperpage?: number;
  navmethod?: string;
  shuffleanswers?: number;
  sumgrades?: number;
  grade?: number;
  timecreated?: number;
  timemodified?: number;
  password?: string;
  subnet?: string;
  browsersecurity?: string;
  delay1?: number;
  delay2?: number;
  showuserpicture?: number;
  showblocks?: number;
  completionattemptsexhausted?: number;
  completionminattempts?: number;
  allowofflineattempts?: number;
  autosaveperiod?: number;
  hasfeedback?: number;
  hasquestions?: number;
  section?: number;
  visible?: number;
  groupmode?: number;
  groupingid?: number;
}

export interface QuizAttempt {
  id: number;
  quiz: number;
  userid: number;
  attempt: number;
  uniqueid: number;
  layout?: string;
  currentpage: number;
  preview: number;
  state: 'inprogress' | 'overdue' | 'finished' | 'abandoned';
  timestart: number;
  timefinish: number;
  timemodified: number;
  timemodifiedoffline?: number;
  timecheckstate?: number;
  sumgrades?: number;
  gradednotificationsenttime?: number;
}

export interface QuizAttemptData {
  attempt: QuizAttempt;
  messages?: string[];
  nextpage: number;
  questions: QuizQuestion[];
  warnings?: unknown[];
}

export interface QuizQuestion {
  slot: number;
  type: string;
  page: number;
  questionnumber?: string;
  number?: number;
  html: string;
  responsefileareas?: unknown[];
  sequencecheck?: number;
  lastactiontime?: number;
  hasautosavedstep?: boolean;
  flagged: boolean;
  state?: string;
  status?: string;
  blockedbyprevious?: boolean;
  mark?: string;
  maxmark?: number;
  settings?: string;
}

export interface QuizAttemptReview {
  attempt: QuizAttempt;
  additionaldata?: Array<{
    id: string;
    title: string;
    content: string;
  }>;
  questions: QuizQuestion[];
  grade?: string;
  warnings?: unknown[];
}

export interface QuizAttemptSummary {
  attempt: QuizAttempt;
  questions: Array<{
    slot: number;
    type: string;
    page: number;
    questionnumber?: string;
    number?: number;
    flagged: boolean;
    state?: string;
    status?: string;
    blockedbyprevious?: boolean;
    hasautosavedstep?: boolean;
    sequencecheck?: number;
    lastactiontime?: number;
  }>;
  warnings?: unknown[];
}

export interface StartAttemptResponse {
  attempt: QuizAttempt;
  warnings?: unknown[];
}

export interface ProcessAttemptResponse {
  state: string;
  warnings?: unknown[];
}

export interface UserBestGrade {
  hasgrade: boolean;
  grade?: number;
  gradetopass?: number;
  warnings?: unknown[];
}

export interface QuizAccessInfo {
  canattempt: boolean;
  canmanage: boolean;
  canpreview: boolean;
  canreviewmyattempts: boolean;
  canviewreports: boolean;
  accessrules: string[];
  activerulenames: string[];
  preventaccessreasons: string[];
  warnings?: unknown[];
}

export interface QuizFeedback {
  feedbacktext: string;
  feedbacktextformat?: number;
  feedbackinlinefiles?: unknown[];
  warnings?: unknown[];
}
