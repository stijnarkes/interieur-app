/**
 * Stijl-onafhankelijke teksten voor de resultatenpagina (heldenblok, rapport-teaser,
 * basispaletstap, accentkleurenstap, aanvraagformulier, bevestiging) — admin-beheerbaar via
 * Teksten (zie SiteContent/QuizConfigController). De waarden hieronder zijn de fallback zolang
 * /api/quiz-config niet (op tijd) antwoordt; remoteConfig.js's applyCopy() overschrijft ze daarna
 * in place, zelfde patroon als data.js's SECTIONS voor de overgangsschermteksten.
 */
const RESULT_HERO_COPY = {
  eyebrow: "Jouw persoonlijke woonstijl",
  expectation:
    "Dit is een eerste richting op basis van wat jij mooi vindt. Onze interieurstylistes helpen je graag om deze stijl te vertalen naar jouw eigen woning.",
  primaryLabel: "Basisstijl",
  secondaryLabel: "Invloed",
};

const REPORT_TEASER_COPY = {
  title: "Jouw persoonlijke interieuradvies staat klaar",
  intro: "Op basis van al jouw keuzes hebben we een persoonlijk woonstijlrapport voor je samengesteld.",
  listIntro: "In jouw rapport vind je onder andere:",
  checklistItems: [
    "Jouw persoonlijke kleurenpalet",
    "Materialen die goed bij jouw stijl passen",
    "Advies voor meubels, vormen en stoffen",
    "Jouw persoonlijke moodboard",
    "Jouw interieurrecept",
    "Tips over wat juist minder goed bij jouw stijl past",
  ],
  mockLabel: "Jouw woonstijlrapport",
};

const BASE_PALETTE_STEP_COPY = {
  title: "Welk basispalet past het beste bij jou?",
  intro: "Elk basispalet vertaalt jouw woonstijl naar een eigen sfeer van kleuren. Kies het palet dat het beste bij jou past.",
  hint: "Kies het palet dat het beste bij jou past.",
  continueLabel: "Doorgaan",
  chosenTitle: "Jouw gekozen basispalet",
  changeLabel: "Wijzig keuze",
  errorMessage: "Je keuze kon niet worden opgeslagen. Probeer het opnieuw.",
};

const ACCENT_COLOR_STEP_COPY = {
  title: "Welke accentkleuren spreken jou het meeste aan?",
  intro: "Je woonstijl hebben we inmiddels goed in beeld. Kies nu maximaal twee kleuren waarmee jij jouw interieur persoonlijk zou maken.",
  hint: "Kies minimaal 1, maximaal 2 kleuren.",
  continueLabel: "Doorgaan",
  chosenTitle: "Jouw gekozen accentkleuren",
  changeLabel: "Wijzig keuze",
  errorMessage: "Je keuze kon niet worden opgeslagen. Probeer het opnieuw.",
  // Alternatief voor het kiezen van 1-2 accentkleuren — niet iedereen wil een kleuraccent, het
  // basispalet hierboven is zelf al de rustige/neutrale keuze. Zie accentColorStep.js.
  skipLabel: "Ik houd het liever bij rustige basiskleuren",
  skippedSummary: "Je hebt gekozen voor rustige basiskleuren, zonder extra accentkleur.",
};

const LEAD_FORM_COPY = {
  heading: "Ontvang jouw persoonlijke woonstijlrapport",
  intro: "Vul hieronder je gegevens in en ontvang jouw complete persoonlijke interieuradvies als PDF in je mailbox.",
  optInLabel: "Ik ontvang graag af en toe wooninspiratie, tips en acties van Boer Staphorst.",
  submitLabel: "Stuur mijn woonstijlrapport",
  reassurance: "Je ontvangt jouw rapport direct per e-mail. Geen verplichtingen.",
  successTitle: "Je woonstijlrapport is verzonden",
  // {name}/{email} worden door lead.js met .replace() ingevuld — zelfde plekhouders als de
  // admin-helptekst op TekstenPage laat zien.
  successBody: "Bedankt, {name}. We hebben jouw persoonlijke woonstijlrapport verstuurd naar {email}.",
  // Getoond zodra de aanvraag in de wachtrij staat maar het versturen zelf nog niet is bevestigd
  // (zie GenerateAndSendQuizResultPdfJob) — nooit een succesmelding claimen die de app nog niet
  // kan waarmaken.
  queuedTitle: "Je aanvraag is ontvangen",
  queuedBody: "Je ontvangt je rapport binnenkort per e-mail.",
  spamHint: "Nog geen e-mail? Kijk voor de zekerheid even in je spam.",
  expectTitle: "Wat kun je verwachten?",
  expectItems: [
    "Jouw persoonlijke woonstijl",
    "Kleuren, materialen en vormen die bij je passen",
    "Een persoonlijk moodboard en interieuradvies",
  ],
  resendLabel: "Opnieuw versturen",
};

export { RESULT_HERO_COPY, REPORT_TEASER_COPY, BASE_PALETTE_STEP_COPY, ACCENT_COLOR_STEP_COPY, LEAD_FORM_COPY };
