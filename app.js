const levels = ["province", "district", "sector", "cell", "village"];
const selects = Object.fromEntries(
  levels.map((level) => [level, document.getElementById(level)]),
);
const resultText = document.getElementById("selection-text");
const contactForm = document.getElementById("contact-form");
const formStatus = document.getElementById("form-status");
const sectionLinks = [...document.querySelectorAll("[data-section]")];
const trackedSections = sectionLinks
  .map((link) => document.getElementById(link.dataset.section))
  .filter(Boolean);
let directory = {};

function setActiveSection(sectionId) {
  sectionLinks.forEach((link) => {
    const isActive = link.dataset.section === sectionId;
    link.classList.toggle("active", isActive);
    if (isActive) link.setAttribute("aria-current", "page");
    else link.removeAttribute("aria-current");
  });
}

sectionLinks.forEach((link) => {
  link.addEventListener("click", () => setActiveSection(link.dataset.section));
});

if (trackedSections.length) {
  const sectionObserver = new IntersectionObserver(
    (entries) => {
      const visibleSection = entries
        .filter((entry) => entry.isIntersecting)
        .sort(
          (first, second) => second.intersectionRatio - first.intersectionRatio,
        )[0];
      if (visibleSection) setActiveSection(visibleSection.target.id);
    },
    { root: document.querySelector(".content"), threshold: [0.2, 0.5, 0.8] },
  );
  trackedSections.forEach((section) => sectionObserver.observe(section));
}

function setOptions(select, names, label) {
  select.innerHTML = `<option value="">${label}</option>`;
  names.forEach((name) => select.add(new Option(name, name)));
  select.disabled = names.length === 0;
}

function resetFrom(levelIndex) {
  levels
    .slice(levelIndex)
    .forEach((level) => setOptions(selects[level], [], `Choose a ${level}`));
}

function updateLocation() {
  const chosen = levels.map((level) => selects[level].value).filter(Boolean);
  resultText.textContent = chosen.length
    ? chosen.join("  /  ")
    : "Your selected location will appear here.";
}

selects.province.addEventListener("change", () => {
  resetFrom(1);
  setOptions(
    selects.district,
    Object.keys(directory[selects.province.value] || {}),
    "Choose a district",
  );
  updateLocation();
});

selects.district.addEventListener("change", () => {
  resetFrom(2);
  const province = directory[selects.province.value] || {};
  setOptions(
    selects.sector,
    Object.keys(province[selects.district.value] || {}),
    "Choose a sector",
  );
  updateLocation();
});

selects.sector.addEventListener("change", () => {
  resetFrom(3);
  const sectors =
    directory[selects.province.value]?.[selects.district.value] || {};
  setOptions(
    selects.cell,
    Object.keys(sectors[selects.sector.value] || {}),
    "Choose a cell",
  );
  updateLocation();
});

selects.cell.addEventListener("change", () => {
  resetFrom(4);
  const cells =
    directory[selects.province.value]?.[selects.district.value]?.[
      selects.sector.value
    ] || {};
  setOptions(
    selects.village,
    cells[selects.cell.value] || [],
    "Choose a village",
  );
  updateLocation();
});

levels
  .slice(1)
  .forEach((level) =>
    selects[level].addEventListener("change", updateLocation),
  );

contactForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  const submitButton = contactForm.querySelector("button");
  submitButton.disabled = true;
  formStatus.textContent = "Sending...";

  try {
    const response = await fetch("api/contact", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(Object.fromEntries(new FormData(contactForm))),
    });
    const result = await response.json();
    if (!response.ok)
      throw new Error(result.error?.message || "Unable to send your message.");
    formStatus.textContent = result.message;
    contactForm.reset();
  } catch (error) {
    formStatus.textContent = error.message;
  } finally {
    submitButton.disabled = false;
  }
});

fetch("data.json")
  .then((response) => response.json())
  .then((data) => {
    directory = data;
    setOptions(selects.province, Object.keys(directory), "Choose a province");
  })
  .catch(() => {
    resultText.textContent =
      "Unable to load the directory. Serve this folder locally and try again.";
  });
