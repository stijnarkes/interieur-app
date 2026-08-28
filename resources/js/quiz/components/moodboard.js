import { QUESTIONS } from "../data.js";
import { placeholderClass } from "./placeholder.js";

// Bepaalt de tegelgrootte in de collage — grotere/dominantere onderdelen krijgen meer ruimte.
// Vloer en bank zijn het meest bepalend voor de sfeer en krijgen daarom de grote tegels.
const TILE_SIZE = {
  floor: "large",
  sofaModel: "large",
  wallFinish: "medium",
  diningTable: "medium",
  coffeeTable: "medium",
  cabinet: "medium",
  wallColor: "small",
  sofaMaterial: "small",
  diningChair: "small",
  lighting: "small",
  rug: "small",
};

function createTile(option, questionTitle, size) {
  const figure = document.createElement("figure");
  figure.className = `moodboard-tile moodboard-tile--${size} ${placeholderClass(option.primaryStyle)}`;

  const img = document.createElement("img");
  img.src = option.image;
  img.alt = `${option.title} in jouw moodboard`;
  img.loading = "lazy";
  img.decoding = "async";
  img.addEventListener("error", () => {
    figure.classList.add("is-placeholder");
    figure.style.setProperty("--tile-label", `"${option.title}"`);
    img.remove();
  }, { once: true });

  figure.appendChild(img);
  return figure;
}

function renderMoodboard(container, answers) {
  container.innerHTML = "";

  const grid = document.createElement("div");
  grid.className = "moodboard-grid";

  QUESTIONS.forEach((question) => {
    // De kleurvoorkeur-vraag heeft geen productfoto (meerkeuze, geen `image`) en hoort niet
    // thuis in de fotocollage — die kleuren komen terug in het kleurenpalet hierboven.
    if (question.type === "color-preference") return;

    const optionId = answers[question.id];
    if (!optionId) return;
    const option = question.options.find((candidate) => candidate.id === optionId);
    if (!option) return;
    grid.appendChild(createTile(option, question.title, TILE_SIZE[question.id] ?? "small"));
  });

  container.appendChild(grid);

  const swatchSourceIds = ["wallColor", "sofaMaterial"];
  const swatches = swatchSourceIds
    .map((questionId) => {
      const question = QUESTIONS.find((item) => item.id === questionId);
      const optionId = answers[questionId];
      return question?.options.find((option) => option.id === optionId);
    })
    .filter((option) => option?.colorHex);

  if (swatches.length > 0) {
    const swatchWrap = document.createElement("div");
    swatchWrap.className = "moodboard-swatches";
    swatches.forEach((option) => {
      const swatch = document.createElement("span");
      swatch.className = "moodboard-swatch";
      swatch.style.background = option.colorHex;
      swatch.title = option.title;
      swatchWrap.appendChild(swatch);
    });
    container.appendChild(swatchWrap);
  }
}

export { renderMoodboard };
