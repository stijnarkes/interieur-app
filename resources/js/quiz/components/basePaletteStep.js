import { saveBasePalette } from "../resultApi.js";
import { BASE_PALETTE_STEP_COPY } from "../copy.js";

/** Eén klikbare basispalet-kaart — sfeernaam, omschrijving en de kleuren als kleine vlakken + naam. Zelfde selectie-chrome (rand/hover/vinkje) als accentColorStep.js's kleurkaarten. */
function createPaletteCard(palette, { selected, onSelect }) {
  const button = document.createElement("button");
  button.type = "button";
  button.className = "base-palette-card";
  button.classList.toggle("is-selected", selected);
  button.setAttribute("aria-pressed", String(selected));

  const check = document.createElement("span");
  check.className = "base-palette-check";
  check.textContent = "✓";
  check.setAttribute("aria-hidden", "true");
  button.appendChild(check);

  const name = document.createElement("span");
  name.className = "base-palette-name";
  name.textContent = palette.name;
  button.appendChild(name);

  const description = document.createElement("span");
  description.className = "base-palette-description";
  description.textContent = palette.description ?? "";
  button.appendChild(description);

  const swatchRow = document.createElement("span");
  swatchRow.className = "base-palette-swatch-row";
  (palette.colors ?? []).forEach((color) => {
    const swatch = document.createElement("span");
    swatch.className = "base-palette-swatch";

    const chip = document.createElement("span");
    chip.className = "base-palette-swatch-color";
    chip.style.background = color.hex;
    swatch.appendChild(chip);

    const label = document.createElement("span");
    label.className = "base-palette-swatch-name";
    label.textContent = color.name;
    swatch.appendChild(label);

    swatchRow.appendChild(swatch);
  });
  button.appendChild(swatchRow);

  button.addEventListener("click", () => onSelect(palette.id));

  return button;
}

/**
 * Eerste stap ná de stijlberekening: de bezoeker kiest één basispalet uit een server-bepaalde set
 * die bij de berekende primaire stijl hoort (zie BasePalette/QuizResultController). Nooit gemengd
 * met een secundaire stijl — elke stijl heeft zijn eigen paletten. Vóór de accentkleurenstap: de
 * daar getoonde kleuren die exact de hex van een kleur uit het gekozen palet delen worden als
 * "Zit al in je basis" getoond (zie accentColorStep.js's `basePaletteColors`-optie).
 *
 * @param {HTMLElement} container
 * @param {{
 *   options: Array<{id: number, name: string, description: ?string, colors: Array<{name: string, hex: string}>}>,
 *   resultUuid: string,
 *   initialPaletteId?: ?number,
 *   onDone: (chosenPalette: {id: number, name: string, description: ?string, colors: Array}) => void,
 *   previewMode?: boolean,
 * }} config
 */
function renderBasePaletteStep(container, { options, resultUuid, initialPaletteId = null, onDone, previewMode = false }) {
  let selectedId = options.some((option) => option.id === initialPaletteId) ? initialPaletteId : null;

  function renderChoice({ statusMessage = "", submitting = false } = {}) {
    container.innerHTML = "";

    const heading = document.createElement("h3");
    heading.textContent = BASE_PALETTE_STEP_COPY.title;
    container.appendChild(heading);

    const intro = document.createElement("p");
    intro.className = "section-intro";
    intro.textContent = BASE_PALETTE_STEP_COPY.intro;
    container.appendChild(intro);

    const grid = document.createElement("div");
    grid.className = "base-palette-grid";

    options.forEach((palette) => {
      const card = createPaletteCard(palette, {
        selected: palette.id === selectedId,
        onSelect: (id) => {
          selectedId = id;
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
    continueBtn.textContent = submitting ? "Bezig..." : BASE_PALETTE_STEP_COPY.continueLabel;
    continueBtn.disabled = submitting || selectedId === null;
    continueBtn.addEventListener("click", async () => {
      const chosenPalette = options.find((option) => option.id === selectedId);
      if (!chosenPalette) return;

      if (previewMode) {
        renderSummary(chosenPalette);
        onDone(chosenPalette);
        return;
      }

      renderChoice({ submitting: true });
      try {
        const response = await saveBasePalette(resultUuid, selectedId);
        renderSummary(response.basePalette);
        onDone(response.basePalette);
      } catch {
        renderChoice({ statusMessage: BASE_PALETTE_STEP_COPY.errorMessage });
      }
    });
    actions.appendChild(continueBtn);
    container.appendChild(actions);

    const status = document.createElement("p");
    status.className = "error";
    status.setAttribute("aria-live", "polite");
    status.textContent = statusMessage;
    container.appendChild(status);
  }

  /** @param {{id: number, name: string, description: ?string, colors: Array}} chosenPalette */
  function renderSummary(chosenPalette) {
    container.innerHTML = "";

    const heading = document.createElement("h3");
    heading.textContent = BASE_PALETTE_STEP_COPY.chosenTitle;
    container.appendChild(heading);

    const card = createPaletteCard(chosenPalette, { selected: true, onSelect: () => {} });
    card.disabled = true;
    container.appendChild(card);

    const changeBtn = document.createElement("button");
    changeBtn.type = "button";
    changeBtn.className = "btn btn-link";
    changeBtn.textContent = BASE_PALETTE_STEP_COPY.changeLabel;
    changeBtn.addEventListener("click", () => renderChoice());
    container.appendChild(changeBtn);
  }

  renderChoice();
}

export { renderBasePaletteStep };
