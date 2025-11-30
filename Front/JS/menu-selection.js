const produits = document.querySelectorAll('.foodItem');
const modal = document.getElementById('modal');
const modalClose = document.getElementById('closeModal');

/* Open - Close modal */
function openModal() {
    modal.style.display = 'flex';
}
function closeModal() {
  modal.style.display = 'none';
  document.querySelector('.first-step').style.display = 'block';
  document.querySelector('.second-step').style.display = 'none';
  document.querySelector('.third-step').style.display = 'none';
}
modalClose.addEventListener('click', closeModal);

/* Select menu */
fetch('./bd.json')
  .then(response => response.json())
  .then(data => {
    window.jsonData = data; 
    createCategories();
    createFoodItems();
    displayFoodByCategory(1); // Affiche la catégorie 1 par défaut
  })
  .catch(error => console.error('Erreur lors du chargement du fichier JSON :', error));

function createCategories() {
  jsonData.categories.forEach((categorie, idx) => {
    const div = document.createElement('div');
    div.classList.add('categorieItem');
    if (idx === 0) div.classList.add('categorieItemSelected');
    div.id = categorie.id;

    const label = document.createElement('div');
    label.classList.add('categorieLabel');
    label.textContent = categorie.nom;

    div.appendChild(label);
    categorieList.appendChild(div);
  });
}

function selectCategory(categoryId) {
  const categories = document.querySelectorAll('.categorieItem');
  categories.forEach(cat => {
    cat.classList.remove('categorieItemSelected');
    if (cat.id == categoryId) {
      cat.classList.add('categorieItemSelected');
    }
  });
}

categorieList.addEventListener('click', (event) => {
  const categoryItem = event.target.closest('.categorieItem');
  if (categoryItem) {
    selectCategory(categoryItem.id);
    displayFoodByCategory(categoryItem.id);
  }
});

function displayFoodByCategory(categoryId) {
  const allFoods = document.querySelectorAll('.foodItem');
  allFoods.forEach(food => {
    food.style.display = food.dataset.category == categoryId ? 'flex' : 'none';
  });
}

function createFoodItems(categoryId) {
  const foodContainer = document.getElementById('foodList');
  const categories = ['menu', 'sandwiches', 'wraps', 'frites', 'boissonsFroides', 'encas', 'desserts'];
  
  categories.forEach(categoryKey => {
    jsonData[categoryKey].forEach(produit => {
      const div = document.createElement('div');
      div.classList.add('foodItem');
      div.dataset.category = produit.categoryId;
      
      div.innerHTML = `
        <img src="./${produit.image}" alt="${produit.nom}" />
        <h3>${produit.nom}</h3>
        <p>${produit.description}</p>
        <span>${produit.prix}€</span>
      `;
      div.addEventListener('click', () => {
        if(produit.categoryId === 1){
          openModal()
        } else
        console.log("nono")
      });
      foodContainer.appendChild(div);
    });
  });
}

document.querySelectorAll('.modalButton.step1').forEach(button => {
  button.addEventListener('click', () => {
    document.querySelector('.first-step').style.display = 'none';
    document.querySelector('.second-step').style.display = 'block';
  });
});

document.querySelectorAll('.modalButton.step2').forEach(button => {
  button.addEventListener('click', () => {
    document.querySelector('.second-step').style.display = 'none';
    document.querySelector('.third-step').style.display = 'block';
  });
});

document.querySelectorAll('.modalButton.step3').forEach(button => {
  button.addEventListener('click', () => {
    alert('Menu ajouté à votre commande !');
    document.querySelector('.first-step').style.display = 'block';
    document.querySelector('.third-step').style.display = 'none';
    closeModal();
  });
}); 
