/** Toont de voortgang binnen het actieve onderdeel, bv. "Materiaal & kleur" / "Vraag 1 van 4". */
function createQuizProgress() {
  const wrap = document.createElement("div");
  wrap.className = "quiz-progress";

  const sectionLabel = document.createElement("p");
  sectionLabel.className = "quiz-progress-section";

  const label = document.createElement("p");
  label.className = "quiz-progress-label";

  const track = document.createElement("div");
  track.className = "quiz-progress-track";
  track.setAttribute("role", "progressbar");
  track.setAttribute("aria-valuemin", "0");

  const fill = document.createElement("span");
  fill.className = "quiz-progress-fill";
  track.appendChild(fill);

  wrap.appendChild(sectionLabel);
  wrap.appendChild(label);
  wrap.appendChild(track);

  function update(sectionTitle, stepInSection, totalInSection) {
    sectionLabel.textContent = sectionTitle;
    label.textContent = `Vraag ${stepInSection} van ${totalInSection}`;
    track.setAttribute("aria-valuemax", String(totalInSection));
    track.setAttribute("aria-valuenow", String(stepInSection));
    fill.style.width = `${(stepInSection / totalInSection) * 100}%`;
  }

  return { element: wrap, update };
}

export { createQuizProgress };
