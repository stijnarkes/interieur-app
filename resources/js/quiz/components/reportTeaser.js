import { composePersonalPalette } from "../paletteEngine.js";
import { createCheckIcon } from "./checkIcon.js";

const CHECKLIST_ITEMS = [
  "Jouw persoonlijke kleurenpalet",
  "Materialen die goed bij jouw stijl passen",
  "Advies voor meubels, vormen en stoffen",
  "Jouw persoonlijke moodboard",
  "Jouw interieurrecept",
  "Tips over wat juist minder goed bij jouw stijl past",
];

function buildMockPage(className) {
  const page = document.createElement("div");
  page.className = `report-mock-page ${className}`;
  return page;
}

/** Puur visuele HTML/CSS-preview van het PDF-rapport — geen afbeelding nodig. */
function buildReportMock(primaryStyle, palette) {
  const mock = document.createElement("div");
  mock.className = "report-mock";

  mock.appendChild(buildMockPage("report-mock-page--back-2"));
  mock.appendChild(buildMockPage("report-mock-page--back-1"));

  const front = buildMockPage("report-mock-page--front");

  const label = document.createElement("p");
  label.className = "report-mock-label";
  label.textContent = "Jouw woonstijlrapport";
  front.appendChild(label);

  const styleName = document.createElement("p");
  styleName.className = "report-mock-style";
  styleName.textContent = primaryStyle?.label ?? "Jouw woonstijl";
  front.appendChild(styleName);

  if (palette.length) {
    const swatches = document.createElement("div");
    swatches.className = "report-mock-swatches";
    palette.slice(0, 5).forEach((color) => {
      const swatch = document.createElement("span");
      swatch.className = "report-mock-swatch";
      swatch.style.background = color.hex;
      swatches.appendChild(swatch);
    });
    front.appendChild(swatches);
  }

  const grid = document.createElement("div");
  grid.className = "report-mock-grid";
  for (let i = 0; i < 4; i++) {
    const tile = document.createElement("span");
    tile.className = "report-mock-tile";
    grid.appendChild(tile);
  }
  front.appendChild(grid);

  mock.appendChild(front);

  return mock;
}

/** Teaserblok direct onder het heldenblok: maakt duidelijk wat er in het PDF-rapport zit. */
function renderReportTeaser(container, { result, answers }) {
  container.innerHTML = "";

  const { primaryStyle } = result;
  const palette = composePersonalPalette(answers, primaryStyle);

  const card = document.createElement("section");
  card.className = "result-card report-teaser";

  const grid = document.createElement("div");
  grid.className = "report-teaser-grid";

  grid.appendChild(buildReportMock(primaryStyle, palette));

  const content = document.createElement("div");
  content.className = "report-teaser-content";

  const title = document.createElement("h3");
  title.textContent = "Jouw persoonlijke interieuradvies staat klaar";
  content.appendChild(title);

  const intro = document.createElement("p");
  intro.className = "section-intro";
  intro.textContent = "Op basis van al jouw keuzes hebben we een persoonlijk woonstijlrapport voor je samengesteld.";
  content.appendChild(intro);

  const listIntro = document.createElement("p");
  listIntro.className = "report-checklist-intro";
  listIntro.textContent = "In jouw rapport vind je onder andere:";
  content.appendChild(listIntro);

  const list = document.createElement("ul");
  list.className = "report-checklist";
  CHECKLIST_ITEMS.forEach((item) => {
    const li = document.createElement("li");
    li.appendChild(createCheckIcon());
    const text = document.createElement("span");
    text.textContent = item;
    li.appendChild(text);
    list.appendChild(li);
  });
  content.appendChild(list);

  grid.appendChild(content);
  card.appendChild(grid);
  container.appendChild(card);
}

export { renderReportTeaser };
