import { saveAccentColors } from "../resultApi.js";
import { ACCENT_COLOR_STEP_COPY } from "../copy.js";

const MAX_SELECTIONS = 2;

/**
 * Eén klikbare kleurkeuze — swatch + naam, met een vinkje-badge als duidelijke selected-state die
 * niet uitsluitend op kleur leunt (zie optionCard.js voor hetzelfde patroon bij foto-opties). Een
 * kleur die exact de hex van een kleur uit het gekozen basispalet deelt (`inBase`) is nooit apart
 * kiesbaar — de naam maakt dan plaats voor een duidelijke uitleg waarom.
 */
function createColorCard(color, { selected, disabled, inBase, onToggle }) {
  const button = document.createElement("button");
  button.type = "button";
  button.className = "accent-color-card";
  button.classList.toggle("is-selected", selected);
  button.classList.toggle("is-in-base", Boolean(inBase));
  button.setAttribute("aria-pressed", String(selected));
  button.disabled = disabled;

  const swatch = document.createElement("span");
  swatch.className = "accent-color-swatch";
  swatch.style.background = color.hex;

  const check = document.createElement("span");
  check.className = "accent-color-check";
  check.textContent = "✓";
  check.setAttribute("aria-hidden", "true");
  swatch.appendChild(check);

  const name = document.createElement("span");
  name.className = "accent-color-name";
  name.textContent = inBase ? "Zit al in je basis" : color.name;

  button.appendChild(swatch);
  button.appendChild(name);
  button.addEventListener("click", () => onToggle(color.id));

  return button;
}

/** Niet-interactieve weergave van een al bevestigde kleurkeuze (zie renderSummary hieronder). */
function createColorChip(color) {
  const chip = document.createElement("div");
  chip.className = "accent-color-chip";

  const swatch = document.createElement("span");
  swatch.className = "accent-color-swatch";
  swatch.style.background = color.hex;
  chip.appendChild(swatch);

  const name = document.createElement("span");
  name.className = "accent-color-name";
  name.textContent = color.name;
  chip.appendChild(name);

  return chip;
}

/**
 * Laatste stap ná de stijlberekening: de bezoeker kiest 1-2 accentkleuren uit een server-bepaalde
 * set die al bij het berekende stijlprofiel past (zie AccentColorSelector/QuizResultController).
 * Beheert zelf de keuze-/bevestigd-substaat en de opslag naar de server (analoog aan hoe
 * lead.js zijn eigen formulier-/successtatus beheert) — quiz.js vangt alleen `onDone` op om
 * daarna de teaser en het leadformulier te tonen.
 *
 * Ondersteunt wijzigen ná bevestigen (de "Wijzig keuze"-knop in de samenvatting) zonder de
 * quizstappen zelf aan te raken — deze stap valt immers ná de laatste vraag, dus de bestaande
 * terug-/volgende-navigatie van de quiz is hier niet van toepassing.
 *
 * @param {HTMLElement} container
 * @param {{
 *   options: Array<{id: number, name: string, hex: string}>,
 *   resultUuid: string,
 *   basePaletteColors?: Array<{name: string, hex: string}>,
 *   initialSelectedIds?: number[],
 *   onSelectionChange?: (ids: number[]) => void,
 *   onDone: (chosenColors: Array<{id: number, name: string, hex: string}>) => void,
 *   previewMode?: boolean,
 * }} config
 */
function renderAccentColorStep(container, { options, resultUuid, basePaletteColors = [], initialSelectedIds = [], onSelectionChange, onDone, previewMode = false }) {
  // Een accentkleur die exact dezelfde hex heeft als een kleur uit het gekozen basispalet ("Zit
  // al in je basis") telt niet meer als aparte, kiesbare optie — zie createColorCard(). Hex-
  // vergelijking case-insensitief, admin/data kan afwijkend casen gebruiken.
  const baseHexes = basePaletteColors.map((color) => (color.hex ?? "").toLowerCase());
  const isInBase = (color) => baseHexes.includes((color.hex ?? "").toLowerCase());

  let selectedIds = initialSelectedIds.filter((id) => {
    const option = options.find((candidate) => candidate.id === id);
    return option && !isInBase(option);
  });

  function renderChoice({ statusMessage = "", submitting = false } = {}) {
    container.innerHTML = "";

    const heading = document.createElement("h3");
    heading.textContent = ACCENT_COLOR_STEP_COPY.title;
    container.appendChild(heading);

    const intro = document.createElement("p");
    intro.className = "section-intro";
    intro.textContent = ACCENT_COLOR_STEP_COPY.intro;
    container.appendChild(intro);

    const hint = document.createElement("p");
    hint.className = "quiz-question-hint";
    hint.textContent = ACCENT_COLOR_STEP_COPY.hint;
    container.appendChild(hint);

    const grid = document.createElement("div");
    grid.className = "accent-color-grid";

    options.forEach((color) => {
      const selected = selectedIds.includes(color.id);
      const inBase = isInBase(color);
      const card = createColorCard(color, {
        selected,
        inBase,
        disabled: inBase || submitting || (!selected && selectedIds.length >= MAX_SELECTIONS),
        onToggle: (id) => {
          if (selectedIds.includes(id)) {
            selectedIds = selectedIds.filter((existingId) => existingId !== id);
          } else if (selectedIds.length < MAX_SELECTIONS) {
            selectedIds = [...selectedIds, id];
          } else {
            return;
          }
          onSelectionChange?.(selectedIds);
          renderChoice();
        },
      });
      grid.appendChild(card);
    });

    container.appendChild(grid);

    const actions = document.createElement("div");
    actions.className = "actions";

    const continueBtn = document.createElement("button");
    continueBtn.type = "button";
    continueBtn.className = "btn btn-primary";
    continueBtn.textContent = submitting ? "Bezig..." : ACCENT_COLOR_STEP_COPY.continueLabel;
    continueBtn.disabled = submitting || selectedIds.length === 0;
    continueBtn.addEventListener("click", async () => {
      // Voorbeeldweergave (zie QuizPreviewController/preview.js): geen echte opslag naar
      // /api/quiz-result/*/accent-colors — de lokaal geselecteerde kleuren zijn al precies wat de
      // server anders zou hebben teruggegeven (allemaal server-aangeleverde, geldige opties).
      if (previewMode) {
        const chosenColors = options.filter((option) => selectedIds.includes(option.id));
        renderSummary(chosenColors);
        onDone(chosenColors);
        return;
      }

      renderChoice({ submitting: true });
      try {
        const response = await saveAccentColors(resultUuid, selectedIds);
        renderSummary(response.accentColors);
        onDone(response.accentColors);
      } catch {
        renderChoice({ statusMessage: ACCENT_COLOR_STEP_COPY.errorMessage });
      }
    });
    actions.appendChild(continueBtn);

    // Alternatief voor 1-2 accentkleuren kiezen: altijd beschikbaar, ook als er nog niets
    // geselecteerd is — het basispalet hierboven is zelf al de rustige/neutrale keuze.
    const skipBtn = document.createElement("button");
    skipBtn.type = "button";
    skipBtn.className = "btn btn-secondary";
    skipBtn.textContent = ACCENT_COLOR_STEP_COPY.skipLabel;
    skipBtn.disabled = submitting;
    skipBtn.addEventListener("click", async () => {
      if (previewMode) {
        renderSkippedSummary();
        onDone([]);
        return;
      }

      renderChoice({ submitting: true });
      try {
        const response = await saveAccentColors(resultUuid, []);
        renderSkippedSummary();
        onDone(response.accentColors);
      } catch {
        renderChoice({ statusMessage: ACCENT_COLOR_STEP_COPY.errorMessage });
      }
    });
    actions.appendChild(skipBtn);

    container.appendChild(actions);

    const status = document.createElement("p");
    status.className = "error";
    status.setAttribute("aria-live", "polite");
    status.textContent = statusMessage;
    container.appendChild(status);
  }

  /** @param {Array<{id: number, name: string, hex: string}>} chosenColors */
  function renderSummary(chosenColors) {
    container.innerHTML = "";

    const heading = document.createElement("h3");
    heading.textContent = ACCENT_COLOR_STEP_COPY.chosenTitle;
    container.appendChild(heading);

    const grid = document.createElement("div");
    grid.className = "accent-color-grid accent-color-grid--summary";
    chosenColors.forEach((color) => grid.appendChild(createColorChip(color)));
    container.appendChild(grid);

    const changeBtn = document.createElement("button");
    changeBtn.type = "button";
    changeBtn.className = "btn btn-link";
    changeBtn.textContent = ACCENT_COLOR_STEP_COPY.changeLabel;
    changeBtn.addEventListener("click", () => renderChoice());
    container.appendChild(changeBtn);
  }

  /** Bevestiging na het bewust overslaan van de accentkleurstap (zie skipBtn hierboven). */
  function renderSkippedSummary() {
    container.innerHTML = "";

    const heading = document.createElement("h3");
    heading.textContent = ACCENT_COLOR_STEP_COPY.chosenTitle;
    container.appendChild(heading);

    const body = document.createElement("p");
    body.className = "section-intro";
    body.textContent = ACCENT_COLOR_STEP_COPY.skippedSummary;
    container.appendChild(body);

    const changeBtn = document.createElement("button");
    changeBtn.type = "button";
    changeBtn.className = "btn btn-link";
    changeBtn.textContent = ACCENT_COLOR_STEP_COPY.changeLabel;
    changeBtn.addEventListener("click", () => renderChoice());
    container.appendChild(changeBtn);
  }

  renderChoice();
}

export { renderAccentColorStep };
