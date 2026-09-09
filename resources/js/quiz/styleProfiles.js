/**
 * Centrale, uitbreidbare configuratie per woonstijl. Dit is de enige plek waar
 * stijl-specifieke content staat — de resultatenpagina en de quizvragen (via data.js) lezen dit
 * generiek uit, er staat nergens stijl-specifieke tekst hardcoded verspreid door componenten.
 *
 * Een nieuwe woonstijl toevoegen = één nieuw object aan STYLE_PROFILES toevoegen; de
 * quizvragen (buildOptions in data.js) en de resultatenpagina passen zich daar automatisch
 * op aan.
 *
 * @typedef {object} StyleColor
 * @property {string} name
 * @property {string} hex
 *
 * @typedef {object} StyleMaterial
 * @property {string} name
 * @property {string} image  pad naar een textuur/sfeerfoto; valt terug op placeholder zolang die niet bestaat
 *
 * @typedef {object} RecipeItem
 * @property {string} label
 * @property {string} value
 *
 * @typedef {object} StyleProfile
 * @property {string} key            interne sleutel, zie InteriorStyle in data.js
 * @property {string} slug           kebab-case, gebruikt in afbeeldingspaden
 * @property {string} label          weergavenaam, bv. "Japandi"
 * @property {string} subtitle       korte persoonlijke tagline voor de hero
 * @property {string} longDescription persoonlijke introductiezin voor de hero
 * @property {string} traitsIntro    korte intro boven de kenmerken-sectie
 * @property {string[]} traits       ca. 5 herkenbare kenmerken
 * @property {string} heroImage      grote sfeerafbeelding voor de hero
 * @property {StyleColor[]} colors   ca. 5 kleuren
 * @property {string} colorTip
 * @property {StyleMaterial[]} materials ca. 4 materialen
 * @property {string} materialsTip
 * @property {{ intro: string, items: string[] }} furnitureAdvice
 * @property {RecipeItem[]} recipe   precies 5 onderdelen: Basis/Grote meubels/Accentkleur/Materialen/Accessoires
 * @property {string} avoid          adviserend geformuleerd, nooit "fout"
 * @property {string[]} productTags  voor toekomstige productmatching (bv. Shopify-tags)
 */

// De 8 huidige woonstijlen. De tekstvelden (subtitle/longDescription/traitsIntro/traits/
// colorTip/materialsTip/furnitureAdvice/recipe/avoid) staan bewust op een korte, neutrale
// placeholder — die tekst wordt apart aangeleverd en in een vervolgstap hier ingevuld. De
// kleuren (colors) zijn al wel representatief ingevuld zodat het kleurenpalet op de
// resultatenpagina er meteen goed uitziet.
/** @type {StyleProfile[]} */
const STYLE_PROFILES = [
  {
    key: "hotelLuxe",
    slug: "hotel-luxe",
    label: "Hotel luxe",
    subtitle: "Tekst volgt",
    longDescription: "Tekst volgt.",
    traitsIntro: "Tekst volgt.",
    traits: ["Tekst volgt"],
    heroImage: "/images/interior/atmosphere/hotel-luxe.webp",
    colors: [
      { name: "Chocoladebruin", hex: "#3e2a20" },
      { name: "Champagnegoud", hex: "#c9a86a" },
      { name: "Diep bordeaux", hex: "#5c2a2e" },
      { name: "Zwart", hex: "#1c1a18" },
      { name: "Warm crème", hex: "#ede2c8" },
    ],
    colorTip: "Tekst volgt.",
    materials: [
      { name: "Tekst volgt", image: "/images/interior/materials/hotel-luxe-1.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/hotel-luxe-2.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/hotel-luxe-3.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/hotel-luxe-4.webp" },
    ],
    materialsTip: "Tekst volgt.",
    furnitureAdvice: {
      intro: "Tekst volgt.",
      items: ["Tekst volgt"],
    },
    recipe: [
      { label: "Basis", value: "Tekst volgt" },
      { label: "Grote meubels", value: "Tekst volgt" },
      { label: "Accentkleur", value: "Tekst volgt" },
      { label: "Materialen", value: "Tekst volgt" },
      { label: "Accessoires", value: "Tekst volgt" },
    ],
    avoid: "Tekst volgt.",
    productTags: ["hotel-luxe"],
  },
  {
    key: "japandi",
    slug: "japandi",
    label: "Japandi",
    subtitle: "Tekst volgt",
    longDescription: "Tekst volgt.",
    traitsIntro: "Tekst volgt.",
    traits: ["Tekst volgt"],
    heroImage: "/images/interior/atmosphere/japandi.webp",
    colors: [
      { name: "Warm wit", hex: "#f5f0e6" },
      { name: "Zand", hex: "#ddc9a0" },
      { name: "Beige", hex: "#cbb188" },
      { name: "Taupe", hex: "#a8967d" },
      { name: "Zacht olijfgroen", hex: "#8d9873" },
    ],
    colorTip: "Tekst volgt.",
    materials: [
      { name: "Tekst volgt", image: "/images/interior/materials/japandi-1.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/japandi-2.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/japandi-3.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/japandi-4.webp" },
    ],
    materialsTip: "Tekst volgt.",
    furnitureAdvice: {
      intro: "Tekst volgt.",
      items: ["Tekst volgt"],
    },
    recipe: [
      { label: "Basis", value: "Tekst volgt" },
      { label: "Grote meubels", value: "Tekst volgt" },
      { label: "Accentkleur", value: "Tekst volgt" },
      { label: "Materialen", value: "Tekst volgt" },
      { label: "Accessoires", value: "Tekst volgt" },
    ],
    avoid: "Tekst volgt.",
    productTags: ["japandi"],
  },
  {
    key: "kleurExplosie",
    slug: "kleur-explosie",
    label: "Kleur explosie",
    subtitle: "Tekst volgt",
    longDescription: "Tekst volgt.",
    traitsIntro: "Tekst volgt.",
    traits: ["Tekst volgt"],
    heroImage: "/images/interior/atmosphere/kleur-explosie.webp",
    colors: [
      { name: "Fel oranje", hex: "#e8622c" },
      { name: "Kobaltblauw", hex: "#1d4e89" },
      { name: "Felgeel", hex: "#f2c94c" },
      { name: "Framboosroze", hex: "#c9184a" },
      { name: "Grasgroen", hex: "#4c9a2a" },
    ],
    colorTip: "Tekst volgt.",
    materials: [
      { name: "Tekst volgt", image: "/images/interior/materials/kleur-explosie-1.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/kleur-explosie-2.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/kleur-explosie-3.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/kleur-explosie-4.webp" },
    ],
    materialsTip: "Tekst volgt.",
    furnitureAdvice: {
      intro: "Tekst volgt.",
      items: ["Tekst volgt"],
    },
    recipe: [
      { label: "Basis", value: "Tekst volgt" },
      { label: "Grote meubels", value: "Tekst volgt" },
      { label: "Accentkleur", value: "Tekst volgt" },
      { label: "Materialen", value: "Tekst volgt" },
      { label: "Accessoires", value: "Tekst volgt" },
    ],
    avoid: "Tekst volgt.",
    productTags: ["kleur-explosie"],
  },
  {
    key: "landelijk",
    slug: "landelijk",
    label: "Landelijk",
    subtitle: "Tekst volgt",
    longDescription: "Tekst volgt.",
    traitsIntro: "Tekst volgt.",
    traits: ["Tekst volgt"],
    heroImage: "/images/interior/atmosphere/landelijk.webp",
    colors: [
      { name: "Zacht wit", hex: "#f7f3ea" },
      { name: "Warm beige", hex: "#e3d3b8" },
      { name: "Zachtgroen (salie)", hex: "#9caf88" },
      { name: "Terracotta", hex: "#c17a53" },
      { name: "Donkerbruin hout", hex: "#5c4433" },
    ],
    colorTip: "Tekst volgt.",
    materials: [
      { name: "Tekst volgt", image: "/images/interior/materials/landelijk-1.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/landelijk-2.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/landelijk-3.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/landelijk-4.webp" },
    ],
    materialsTip: "Tekst volgt.",
    furnitureAdvice: {
      intro: "Tekst volgt.",
      items: ["Tekst volgt"],
    },
    recipe: [
      { label: "Basis", value: "Tekst volgt" },
      { label: "Grote meubels", value: "Tekst volgt" },
      { label: "Accentkleur", value: "Tekst volgt" },
      { label: "Materialen", value: "Tekst volgt" },
      { label: "Accessoires", value: "Tekst volgt" },
    ],
    avoid: "Tekst volgt.",
    productTags: ["landelijk"],
  },
  {
    key: "modern",
    slug: "modern",
    label: "Modern",
    subtitle: "Tekst volgt",
    longDescription: "Tekst volgt.",
    traitsIntro: "Tekst volgt.",
    traits: ["Tekst volgt"],
    heroImage: "/images/interior/atmosphere/modern.webp",
    colors: [
      { name: "Wit", hex: "#f5f5f4" },
      { name: "Lichtgrijs", hex: "#d4d4d2" },
      { name: "Antraciet", hex: "#33363a" },
      { name: "Zwart", hex: "#17181a" },
      { name: "Staalblauw", hex: "#4a6fa5" },
    ],
    colorTip: "Tekst volgt.",
    materials: [
      { name: "Tekst volgt", image: "/images/interior/materials/modern-1.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/modern-2.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/modern-3.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/modern-4.webp" },
    ],
    materialsTip: "Tekst volgt.",
    furnitureAdvice: {
      intro: "Tekst volgt.",
      items: ["Tekst volgt"],
    },
    recipe: [
      { label: "Basis", value: "Tekst volgt" },
      { label: "Grote meubels", value: "Tekst volgt" },
      { label: "Accentkleur", value: "Tekst volgt" },
      { label: "Materialen", value: "Tekst volgt" },
      { label: "Accessoires", value: "Tekst volgt" },
    ],
    avoid: "Tekst volgt.",
    productTags: ["modern"],
  },
  {
    key: "modernLuxe",
    slug: "modern-luxe",
    label: "Modern luxe",
    subtitle: "Tekst volgt",
    longDescription: "Tekst volgt.",
    traitsIntro: "Tekst volgt.",
    traits: ["Tekst volgt"],
    heroImage: "/images/interior/atmosphere/modern-luxe.webp",
    colors: [
      { name: "Zwart", hex: "#1a1a1a" },
      { name: "Marmerwit", hex: "#eceae4" },
      { name: "Goud", hex: "#b08d57" },
      { name: "Antraciet", hex: "#3a3d40" },
      { name: "Dieppetrol", hex: "#1f4045" },
    ],
    colorTip: "Tekst volgt.",
    materials: [
      { name: "Tekst volgt", image: "/images/interior/materials/modern-luxe-1.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/modern-luxe-2.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/modern-luxe-3.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/modern-luxe-4.webp" },
    ],
    materialsTip: "Tekst volgt.",
    furnitureAdvice: {
      intro: "Tekst volgt.",
      items: ["Tekst volgt"],
    },
    recipe: [
      { label: "Basis", value: "Tekst volgt" },
      { label: "Grote meubels", value: "Tekst volgt" },
      { label: "Accentkleur", value: "Tekst volgt" },
      { label: "Materialen", value: "Tekst volgt" },
      { label: "Accessoires", value: "Tekst volgt" },
    ],
    avoid: "Tekst volgt.",
    productTags: ["modern-luxe"],
  },
  {
    key: "natuurlijk",
    slug: "natuurlijk",
    label: "Natuurlijk",
    subtitle: "Tekst volgt",
    longDescription: "Tekst volgt.",
    traitsIntro: "Tekst volgt.",
    traits: ["Tekst volgt"],
    heroImage: "/images/interior/atmosphere/natuurlijk.webp",
    colors: [
      { name: "Mosgroen", hex: "#6b7a53" },
      { name: "Zandbeige", hex: "#d8c3a0" },
      { name: "Steengrijs", hex: "#8a9a99" },
      { name: "Warme terracotta", hex: "#b5673a" },
      { name: "Naturel houtbruin", hex: "#7a5c3e" },
    ],
    colorTip: "Tekst volgt.",
    materials: [
      { name: "Tekst volgt", image: "/images/interior/materials/natuurlijk-1.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/natuurlijk-2.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/natuurlijk-3.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/natuurlijk-4.webp" },
    ],
    materialsTip: "Tekst volgt.",
    furnitureAdvice: {
      intro: "Tekst volgt.",
      items: ["Tekst volgt"],
    },
    recipe: [
      { label: "Basis", value: "Tekst volgt" },
      { label: "Grote meubels", value: "Tekst volgt" },
      { label: "Accentkleur", value: "Tekst volgt" },
      { label: "Materialen", value: "Tekst volgt" },
      { label: "Accessoires", value: "Tekst volgt" },
    ],
    avoid: "Tekst volgt.",
    productTags: ["natuurlijk"],
  },
  {
    key: "scandinavisch",
    slug: "scandinavisch",
    label: "Scandinavisch",
    subtitle: "Tekst volgt",
    longDescription: "Tekst volgt.",
    traitsIntro: "Tekst volgt.",
    traits: ["Tekst volgt"],
    heroImage: "/images/interior/atmosphere/scandinavisch.webp",
    colors: [
      { name: "Wit", hex: "#fbfbf9" },
      { name: "Lichtgrijs", hex: "#dfe1e0" },
      { name: "Lichthout beige", hex: "#e8ddc9" },
      { name: "Zacht blauw", hex: "#a9c2d0" },
      { name: "Zwart", hex: "#232323" },
    ],
    colorTip: "Tekst volgt.",
    materials: [
      { name: "Tekst volgt", image: "/images/interior/materials/scandinavisch-1.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/scandinavisch-2.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/scandinavisch-3.webp" },
      { name: "Tekst volgt", image: "/images/interior/materials/scandinavisch-4.webp" },
    ],
    materialsTip: "Tekst volgt.",
    furnitureAdvice: {
      intro: "Tekst volgt.",
      items: ["Tekst volgt"],
    },
    recipe: [
      { label: "Basis", value: "Tekst volgt" },
      { label: "Grote meubels", value: "Tekst volgt" },
      { label: "Accentkleur", value: "Tekst volgt" },
      { label: "Materialen", value: "Tekst volgt" },
      { label: "Accessoires", value: "Tekst volgt" },
    ],
    avoid: "Tekst volgt.",
    productTags: ["scandinavisch"],
  },
];

function getStyleProfile(key) {
  return STYLE_PROFILES.find((profile) => profile.key === key) ?? null;
}

export { STYLE_PROFILES, getStyleProfile };
