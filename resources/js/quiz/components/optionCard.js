import { placeholderClass } from "./placeholder.js";

/**
 * Rendert één foto-keuzekaart. Toont de afbeelding op `option.image` zodra die bestaat;
 * tot die tijd (of bij een laadfout) valt de kaart automatisch terug op een nette placeholder.
 * Zet later gewoon het echte bestand op hetzelfde pad neer — geen codewijziging nodig.
 */
function createOptionCard(option, { questionTitle, selected, onSelect }) {
  const button = document.createElement("button");
  button.type = "button";
  button.className = "option-card";
  button.classList.toggle("is-selected", selected);
  button.setAttribute("aria-pressed", String(selected));

  const imageWrap = document.createElement("span");
  imageWrap.className = `option-image ${placeholderClass(option.primaryStyle)}`;

  const img = document.createElement("img");
  img.src = option.image;
  img.alt = `${option.title} — optie bij "${questionTitle}"`;
  img.loading = "lazy";
  img.decoding = "async";
  img.addEventListener("error", () => {
    imageWrap.classList.add("is-placeholder");
    img.remove();
  }, { once: true });

  imageWrap.appendChild(img);

  const label = document.createElement("span");
  label.className = "option-label";
  label.textContent = option.title;

  const check = document.createElement("span");
  check.className = "option-check";
  check.textContent = "✓";
  check.setAttribute("aria-hidden", "true");

  button.appendChild(imageWrap);
  button.appendChild(label);
  button.appendChild(check);

  button.addEventListener("click", () => onSelect(option.id));

  return button;
}

export { createOptionCard };
