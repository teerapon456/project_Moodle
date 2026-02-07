// Moodle API Types - File

export interface MoodleFile {
  contextid: number;
  component: string;
  filearea: string;
  itemid: number;
  filepath: string;
  filename: string;
  isdir: boolean;
  isimage?: boolean;
  timemodified: number;
  timecreated?: number;
  filesize?: number;
  author?: string;
  license?: string;
  filenameshort?: string;
  filesizeformatted?: string;
  icon?: string;
  timecreatedformatted?: string;
  timemodifiedformatted?: string;
  url?: string;
}

export interface FilesResponse {
  parents: Array<{
    contextid: number;
    component: string;
    filearea: string;
    itemid: number;
    filepath: string;
    filename: string;
  }>;
  files: MoodleFile[];
  warnings?: unknown[];
}

export interface UploadFileParams {
  component: string;
  filearea: string;
  itemid: number;
  filepath: string;
  filename: string;
  filecontent: string;
  contextlevel?: string;
  instanceid?: number;
}

export interface UploadFileResponse {
  contextid: number;
  component: string;
  filearea: string;
  itemid: number;
  filepath: string;
  filename: string;
  url: string;
}

export interface DraftFile {
  filename: string;
  filepath: string;
  filesize: number;
  fileurl: string;
  timemodified: number;
  mimetype: string;
  isexternalfile?: boolean;
}

export interface PrivateFilesInfo {
  filecount: number;
  foldercount: number;
  filesize: number;
  filesizewithoutreferences: number;
  warnings?: unknown[];
}
