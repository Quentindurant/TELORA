// Fonctions pour la gestion des contacts

document.addEventListener('DOMContentLoaded', function() {
    // Obtenir l'ID du client depuis l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const clientId = urlParams.get('idclients');

    // Vérifier si clientId existe
    if (!clientId) {
        console.error('ID client manquant');
        return;
    }

    // Recherche globale AJAX sur tous les contacts
    const searchInput = document.getElementById('contactSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const value = this.value.trim();
            if (!value) {
                // Si champ vide, recharge la page pour pagination normale
                location.href = location.pathname + location.search;
                return;
            }
            fetch('search_contacts.php?idclients=' + encodeURIComponent(clientId) + '&q=' + encodeURIComponent(value))
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const contactGrid = document.querySelector('.contact-grid');
                        if (contactGrid) {
                            contactGrid.innerHTML = '';
                            if (data.contacts.length === 0) {
                                contactGrid.innerHTML = '<div style="text-align:center;color:#888;width:100%">Aucun résultat</div>';
                            } else {
                                data.contacts.forEach(contact => {
                                    let societeOrNom = contact.Societe ? contact.Societe : contact.Nom;
                                    let card = document.createElement('div');
                                    card.className = 'contact-card';
                                    card.innerHTML = `
                                        <div class="card-logo">
                                            <div class="logo-placeholder">${(contact.Nom||'').substring(0,2).toUpperCase()}</div>
                                        </div>
                                        <div class="card-body">
                                            <div class="client-name">${societeOrNom}</div>
                                            <div class="client-details">
                                                ${contact.Telephone ? `<div class='detail-item'><i class='fas fa-phone'></i> <span>${contact.Telephone}</span></div>` : ''}
                                                ${contact.Email ? `<div class='detail-item'><i class='fas fa-envelope'></i> <span>${contact.Email}</span></div>` : ''}
                                            </div>
                                            <div class="card-actions">
                                                <button class="btn-icon" onclick="openEditContactModal(${contact.iduser_annuaire}, ${clientId})"><i class="fas fa-edit"></i></button>
                                                <button class="btn-icon btn-delete" onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce contact ?')) window.location.href='?action=delete&id=${contact.iduser_annuaire}&idclients=${clientId}'"><i class="fas fa-trash-alt"></i></button>
                                            </div>
                                        </div>
                                    `;
                                    contactGrid.appendChild(card);
                                });
                            }
                        }
                    }
                });
        });
    } else {
        console.warn('Champ de recherche de contact (contactSearchInput) introuvable dans le DOM');
    }

    // Gestionnaire du formulaire
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(contactForm);
            const contactId = document.getElementById('contactId')?.value;
            const url = contactId ? 'update_contact.php' : 'add_contact.php';
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Une erreur est survenue');
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'envoi du formulaire :', error);
                alert('Une erreur est survenue lors de l\'envoi du formulaire');
            });
        });
    } else {
        console.warn('Formulaire de contact (contactForm) introuvable dans le DOM');
    }

    // Gestionnaire du formulaire d'ajout de contact
    const addContactForm = document.getElementById('addContactForm');
    if (addContactForm) {
        addContactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(addContactForm);
            fetch('annuaire.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('addContactModal').style.display = 'none';
                    location.reload();
                } else {
                    alert(data.error || 'Erreur lors de l\'ajout du contact');
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'ajout du contact :', error);
                alert('Une erreur est survenue lors de l\'ajout du contact');
            });
        });
    }

    // Gestion de la suppression des contacts
    document.querySelectorAll('.btn-delete').forEach(button => {
        if (button) {
            button.addEventListener('click', function() {
                const contactId = this.dataset.id;
                if (confirm('Êtes-vous sûr de vouloir supprimer ce contact ?')) {
                    fetch('delete_contact.php?id=' + contactId)
                        .then(() => window.location.reload());
                }
            });
        } else {
            console.warn('Bouton de suppression de contact (.btn-delete) introuvable dans le DOM');
        }
    });

    // Gestion de la sélection multiple
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.contact-select').forEach(checkbox => {
                if (checkbox) {
                    checkbox.checked = this.checked;
                } else {
                    console.warn('Case à cocher de sélection de contact (.contact-select) introuvable dans le DOM');
                }
            });
        });
    } else {
        console.warn('Case à cocher de sélection de tous les contacts (selectAll) introuvable dans le DOM');
    }

    // Gestion de l'importation CSV (VPS style)
    const importBtn = document.getElementById('import-csv-btn');
    if (importBtn) {
        importBtn.addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.csv';
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;
                const formData = new FormData();
                formData.append('csv_file', file); // IMPORTANT : champ comme sur le VPS !
                formData.append('idclients', clientId);
                fetch('import_csv.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Erreur : ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de l\'importation');
                });
            });
            input.click();
        });
    } else {
        console.warn('Bouton import-csv-btn introuvable dans le DOM');
    }

    // Gestion de l'exportation CSV (VPS style)
    const exportBtn = document.getElementById('export-csv-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            window.location.href = 'handle_csv.php?action=export_csv&idclients=' + clientId;
        });
    } else {
        console.warn('Bouton export-csv-btn introuvable dans le DOM');
    }

    // Fonctions modales
    function openAddContactModal() {
        const modal = document.getElementById('contactModal');
        const modalTitle = document.getElementById('modalTitle');
        const contactForm = document.getElementById('contactForm');
        
        if (modal && modalTitle && contactForm) {
            modalTitle.textContent = 'Ajouter un contact';
            contactForm.reset();
            document.getElementById('contactId').value = '';
            
            modal.style.display = 'block';
        } else {
            console.warn('Modal de contact (contactModal) ou formulaire de contact (contactForm) introuvable dans le DOM');
        }
    }

    function editContact(contactId) {
        const modal = document.getElementById('contactModal');
        const modalTitle = document.getElementById('modalTitle');
        const contactForm = document.getElementById('contactForm');
        
        if (modal && modalTitle && contactForm) {
            modalTitle.textContent = 'Modifier le contact';
            
            // Récupérer les données du contact
            fetch(`get_contact.php?id=${contactId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('contactId').value = data.id;
                    document.getElementById('nom').value = data.nom;
                    document.getElementById('prenom').value = data.prenom;
                    document.getElementById('email').value = data.email;
                    document.getElementById('telephone').value = data.telephone;
                    document.getElementById('societe').value = data.societe;
                    modal.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erreur lors de la récupération des données du contact');
                });
        } else {
            console.warn('Modal de contact (contactModal) ou formulaire de contact (contactForm) introuvable dans le DOM');
        }
    }

    function deleteContact(contactId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce contact ?')) {
            fetch('delete_contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: contactId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la suppression');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de la suppression');
            });
        }
    }

    function closeModal() {
        const modal = document.getElementById('contactModal');
        if (modal) {
            modal.style.display = 'none';
        } else {
            console.warn('Modal de contact (contactModal) introuvable dans le DOM');
        }
    }

    // Fermer la modal si on clique en dehors
    window.onclick = function(event) {
        const modal = document.getElementById('contactModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Fonction d'export CSV
    document.getElementById('export-btn').addEventListener('click', function() {
        const contacts = [];
        const cards = document.querySelectorAll('.contact-card');
        
        if (cards) {
            cards.forEach(card => {
                const name = card.querySelector('.client-name').textContent.trim();
                const nameParts = name.split(' ');
                const contact = {
                    nom: nameParts[0] || '',
                    prenom: nameParts.slice(1).join(' ') || '',
                    telephone: card.querySelector('.fa-phone') ? 
                        card.querySelector('.fa-phone').nextElementSibling.textContent.trim() : '',
                    email: card.querySelector('.fa-envelope') ? 
                        card.querySelector('.fa-envelope').nextElementSibling.textContent.trim() : '',
                    societe: card.querySelector('.fa-building') ? 
                        card.querySelector('.fa-building').nextElementSibling.textContent.trim() : ''
                };
                contacts.push(contact);
            });
            
            let csv = 'Nom,Prénom,Téléphone,Email,Société\n';
            contacts.forEach(contact => {
                csv += `"${contact.nom}","${contact.prenom}","${contact.telephone}","${contact.email}","${contact.societe}"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'contacts.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            console.warn('Cartes de contact (.contact-card) introuvables dans le DOM');
        }
    });

    // Gestion du modal
    const modal = document.getElementById('addContactModal');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = modal.querySelector('.btn-outline');

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
        } else {
            console.warn('Modal d\'ajout de contact (addContactModal) introuvable dans le DOM');
        }
    }

    if (closeBtn) {
        closeBtn.onclick = closeModal;
    }

    if (cancelBtn) {
        cancelBtn.onclick = closeModal;
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }

    // Validation du formulaire
    const form = modal.querySelector('form');
    if (form) {
        form.onsubmit = function(e) {
            const telephone = form.querySelector('[name="Telephone"]').value;
            const nom = form.querySelector('[name="Nom"]').value;

            if (!telephone || !nom) {
                e.preventDefault();
                alert('Le nom et le numéro de téléphone sont obligatoires');
                return false;
            }

            // Validation basique du format de téléphone
            const phoneRegex = /^[0-9+\-\s()]{10,}$/;
            if (!phoneRegex.test(telephone.replace(/\s/g, ''))) {
                e.preventDefault();
                alert('Le format du numéro de téléphone est invalide');
                return false;
            }

            // Validation de l'email si présent
            const email = form.querySelector('[name="Email"]').value;
            if (email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Le format de l\'email est invalide');
                    return false;
                }
            }

            return true;
        };
    } else {
        console.warn('Formulaire d\'ajout de contact introuvable dans le DOM');
    }
});
