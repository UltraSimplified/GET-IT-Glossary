import type {
    DefinitionStyle,
    HomeAlphabet,
    LetterColours,
    LetterShape,
    TermEntry,
    TermStatus,
    VersionMeta,
} from '@/types/glossary';

/** One row of the term management list (the current version of each term). */
export type AdminTermRow = {
    id: number;
    master_id: number;
    term: string;
    path: string;
    tags: string[];
    version: number;
    status: TermStatus;
    idgi: number | null;
    editors: string[];
};

/** The editable fields of a term, as the edit screen receives them. */
export type TermFields = {
    term: string;
    term_ordered: string | null;
    term_status: TermStatus;
    do_not_use: boolean;
    tags: string[];
    see_also: string | null;
    synonyms: string | null;
    warning: string | null;
    definition: string | null;
    explanation: string | null;
    common_variants: string | null;
    pronunciation: string | null;
    video: string | null;
    test: string | null;
    cartoon: string | null;
    example: string | null;
};

export type EditableTerm = TermFields & {
    id: number;
    master_id: number;
    version: number;
    path: string;
};

export type EditorChoices = {
    all: { id: number; name: string }[];
    assigned: number[];
};

export type ComparedVersion = TermEntry & {
    status: TermStatus;
    meta: VersionMeta;
};

export type AdminPageRow = {
    id: number;
    title: string;
    excerpt: string;
    slug: string;
    path: string | null;
    menu: number | null;
};

export type EditablePage = {
    id: number;
    title: string;
    body: string;
    slug: string;
    menu: number | null;
    position: number | null;
    path: string | null;
};

export type AdminUserRow = {
    id: number;
    name: string;
    email: string;
    role: 'Admin' | 'Editor' | 'Contributor';
    terms: number;
};

export type EditableUser = {
    id: number;
    first_name: string;
    last_name: string;
    username: string | null;
    email: string;
    is_admin: boolean;
    name: string;
};

export type AssignableTerm = {
    master_id: number;
    term: string;
    assigned: boolean;
};

/** Footer columns a page can be listed in. */
export const PAGE_MENUS: { value: number; label: string }[] = [
    { value: 1, label: 'About' },
    { value: 2, label: 'Get the API' },
    { value: 3, label: 'Get in touch' },
];

/** The site's details, as the site screen receives them. */
export type EditableSite = {
    name: string;
    tagline: string;
    locale: string;
    logo: string | null;
    /** The "I don't get it" pictures; null where the site has none. */
    idgi: IdgiPictures;
    font_stylesheet_url: string | null;
    font_families: string[];
};

export type IdgiPictureSlot = 'rest' | 'hover' | 'thanks';

export type IdgiPictures = Record<IdgiPictureSlot, string | null>;

export type Option = { value: string; label: string };

/** The eleven colours a theme sets, in the order the editor shows them. */
export const COLOUR_ROLES = [
    'background',
    'surface',
    'text',
    'muted_text',
    'border',
    'link',
    'primary',
    'primary_text',
    'accent',
    'highlight',
    'highlight_text',
] as const;

export type ColourRole = (typeof COLOUR_ROLES)[number];

export type Palette = Record<ColourRole, string>;

export type FontChoice = {
    family: string;
    source: 'bundled' | 'site';
    fallback: string;
};

/** A theme's settings (app/Themes/ThemeSettings.php describes them). */
export type ThemeSettings = {
    version: number;
    colours: { light: Palette; dark: Palette | null };
    accents: string[];
    fonts: { body: FontChoice; heading: FontChoice };
    base_size: number;
    radius: number;
    logo: string | null;
    letter_shape: LetterShape;
    home_alphabet: HomeAlphabet;
    definition_style: DefinitionStyle;
    letter_colours: LetterColours;
};

/** One theme on the themes screen. */
export type ThemeRow = {
    key: string;
    name: string;
    description: string | null;
    built_in: boolean;
    based_on: string | null;
    active: boolean;
    colours: Palette;
    accents: string[];
    has_dark: boolean;
    letter_shape: LetterShape;
    font: string;
};

/** A custom theme, as the theme editor receives it. */
export type EditableTheme = {
    key: string;
    name: string;
    based_on: string | null;
    active: boolean;
    settings: ThemeSettings;
};

/** The CSS variables ThemeStyles makes from a theme's settings. */
export type ThemeVariables = {
    light: Record<string, string>;
    dark: Record<string, string> | null;
};

/** What a reader said after pressing "I don't get it", as editors see it. */
export type IdgiReply = {
    id: number;
    message: string;
    date: string | null;
    datetime: string | null;
    version: number;
    /** About a version older than the one being edited. */
    earlier: boolean;
    handled: { by: string; date: string | null } | null;
};
