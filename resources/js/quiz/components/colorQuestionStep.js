/**
 * Rendert de kleurvoorkeur-vraag: een enkele keuze uit kant-en-klare sfeerpaletten (elk een
 * setje van 5 kleuren + een sfeernaam), in plaats van losse kleurswatches. Bewust een ander
 * component dan questionStep.js/optionCard.js qua opmaak (kleurclusters i.p.v. foto's), maar
 * met dezelfde enkele-keuze-interactie (klik = direct gekozen) als de rest van de quiz.
 */
function renderColorQuestionStep(container, question, selectedOptionId, onSelect) {
  container.innerHTML = "";

  const heading = document.createElement("h2");
  heading.className = "quiz-question-title";
  heading.textContent = question.title;
  container.appendChild(heading);

  if (question.subtitle) {
    const subtitle = document.createElement("p");
    subtitle.className = "quiz-question-subtitle";
    subtitle.textContent = question.subtitle;
    container.appendChild(subtitle);
  }

  const grid = document.createElement("div");
  grid.className = "palette-option-grid";

  question.options.forEach((option) => {
    const isSelected = option.id === selectedOptionId;

    const button = document.createElement("button");
    button.type = "button";
    button.className = "option-card palette-option-card";
    button.classList.toggle("is-selected", isSelected);
    button.setAttribute("aria-pressed", String(isSelected));

    const swatches = document.createElement("span");
    swatches.className = "palette-option-swatches";
    (option.colors ?? []).forEach((color) => {
      const chip = document.createElement("span");
      chip.className = "palette-option-chip";
      chip.style.background = color.hex;
      swatches.appendChild(chip);
    });

    const name = document.createElement("span");
    name.className = "option-label";
    name.textContent = option.title;

    const check = document.createElement("span");
    check.className = "option-check";
    check.textContent = "✓";
    check.setAttribute("aria-hidden", "true");

    button.appendChild(swatches);
    button.appendChild(name);
    button.appendChild(check);
    button.addEventListener("click", () => onSelect(option.id));

    grid.appendChild(button);
  });

  container.appendChild(grid);
}

export { renderColorQuestionStep };
