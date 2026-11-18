export type FooterLink = { label: string; href: string };

export type FooterColumn = { title: string; links: FooterLink[] };

/** Site-wide data shared with every page by HandleInertiaRequests. */
export type GlossaryShared = {
    title: string;
    tagline: string;
    logo: string;
    /** The site's "I don't get it" pictures, or null for the built-in bubbles. */
    idgi: { rest: string; hover: string; thanks: string } | null;
    locale: string;
    letters: string[];
    footer: FooterColumn[];
    canRegister: boolean;
};

export type LetterShape = 'plain' | 'square' | 'hexagon' | 'circle';

export type HomeAlphabet = 'row' | 'grid' | 'honeycomb';

export type DefinitionStyle = 'plain' | 'italic-dash';

export type LetterColours = 'single' | 'cycle';

/** The active theme's switches and accents, shared with every page. */
export type ThemeShared = {
    key: string;
    name: string;
    letter_shape: LetterShape;
    home_alphabet: HomeAlphabet;
    definition_style: DefinitionStyle;
    letter_colours: LetterColours;
    accents: string[];
    has_dark: boolean;
};

/** A term as it appears in a list. The *_html fields hold editor-written markup. */
export type TermSummary = {
    id: number;
    term: string;
    term_ordered: string;
    path: string;
    do_not_use: boolean;
    synonyms_html: string;
    definition_html: string;
};

export type ListedTerm = TermSummary & {
    /** Set when the entry is a cross-reference filed under another name. */
    listed_as: string | null;
};

export type SearchResult = TermSummary & { term_html: string };

export type RankedTerm = TermSummary & { hits: number };

export type TermLink = { term: string; path: string };

export type TermEntry = {
    id: number;
    master_id: number;
    term: string;
    path: string;
    version: number;
    do_not_use: boolean;
    definition_html: string;
    synonyms_html: string;
    explanation_html: string;
    cartoon_html: string;
    video_html: string;
    example_html: string;
    links_html: string;
    warning_html: string;
    see_also: TermLink[];
    tags: string[];
    /** Public path of a recording of the term said aloud. */
    pronunciation: string | null;
};

export type TermComment = {
    id: number;
    comment: string;
    author_id: number;
    author_name: string;
    date: string | null;
    can_delete: boolean;
};

export type IdgiScore = { percent: number; count: number; hits: number };

export type VersionMeta = {
    version: number;
    updated_by: string | null;
    date: string | null;
    idgi: IdgiScore | null;
};

export type TermStatus = 'DRAFT' | 'LIVE' | 'ARCHIVED' | 'REVISION';

export type ContentPage = {
    id: number;
    slug: string;
    title: string;
    body_html: string;
};

export type ContactFormKind =
    | 'feedback'
    | 'suggest-term'
    | 'suggest-translation';
