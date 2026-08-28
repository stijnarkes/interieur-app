/**
 * Toont de hoofdvoortgang van de test: welke van de 3 onderdelen actief/afgerond/nog te
 * gaan is, plus een dunne totale voortgangsbalk over alle stappen heen. Het aantal
 * onderdelen en stappen wordt volledig afgeleid van `sections`/`totalSteps` — geen
 * hardcoded aantallen.
 */
function createSectionStepper(sections, totalSteps) {
  const wrap = document.createElement("div");
  wrap.className = "section-stepper-wrap";

  const list = document.createElement("ol");
  list.className = "section-stepper";
  list.setAttribute("aria-label", "Onderdelen van de stijltest");

  const items = sections.map((section, index) => {
    const li = document.createElement("li");
    li.className = "section-step";

    const num = document.createElement("span");
    num.className = "section-step-num";
    num.textContent = String(index + 1);

    const label = document.createElement("span");
    label.className = "section-step-label";
    label.textContent = section.title;

    li.appendChild(num);
    li.appendChild(label);
    list.appendChild(li);

    if (index < sections.length - 1) {
      const arrow = document.createElement("li");
      arrow.className = "section-step-arrow";
      arrow.setAttribute("aria-hidden", "true");
      arrow.textContent = "→";
      list.appendChild(arrow);
    }

    return li;
  });

  wrap.appendChild(list);

  const overallTrack = document.createElement("div");
  overallTrack.className = "overall-progress-track";
  const overallFill = document.createElement("span");
  overallFill.className = "overall-progress-fill";
  overallTrack.appendChild(overallFill);
  wrap.appendChild(overallTrack);

  function update(activeSectionIndex, completedSteps) {
    items.forEach((li, index) => {
      li.classList.toggle("is-active", index === activeSectionIndex);
      li.classList.toggle("is-done", index < activeSectionIndex);
    });
    overallFill.style.width = `${Math.min(100, (completedSteps / totalSteps) * 100)}%`;
  }

  return { element: wrap, update };
}

export { createSectionStepper };
