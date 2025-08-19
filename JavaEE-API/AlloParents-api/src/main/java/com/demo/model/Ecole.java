package com.demo.model;

import jakarta.json.bind.annotation.JsonbProperty;
import jakarta.persistence.*;

@Entity
@Table(name = "ecole")
public class Ecole {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    
    @Column(name = "nom", nullable = false)
    private String nom;
    
    @Column(name = "adresse", nullable = false)
    private String adresse;
    
    @Column(name = "ville", nullable = false)
    private String ville;
    
    @Column(name = "code_postal", nullable = false)
    private String codePostal;
    
    @Column(name = "telephone")
    private String telephone;
    
    @Column(name = "email")
    private String email;
    
    @Column(name = "valide", nullable = false)
    private Boolean valide = false; // Par défaut, une école n'est pas validée
    
    @Column(name = "contributeur_id")
    private Long contributeurId; // ID de l'utilisateur qui a proposé l'école
    
    @Column(name = "commentaire_admin")
    private String commentaireAdmin;

    public Ecole() {
    }

    public Ecole(String nom, String adresse, String ville, String codePostal) {
        this.nom = nom;
        this.adresse = adresse;
        this.ville = ville;
        this.codePostal = codePostal;
    }

    public Ecole(Long id, String nom, String adresse, String ville, String codePostal) {
        this.id = id;
        this.nom = nom;
        this.adresse = adresse;
        this.ville = ville;
        this.codePostal = codePostal;
    }

    @JsonbProperty("id")
    public Long getId() {
        return id;
    }

    public void setId(Long id) {
        this.id = id;
    }

    @JsonbProperty("nom")
    public String getNom() {
        return nom;
    }

    public void setNom(String nom) {
        this.nom = nom;
    }

    @JsonbProperty("adresse")
    public String getAdresse() {
        return adresse;
    }

    public void setAdresse(String adresse) {
        this.adresse = adresse;
    }

    @JsonbProperty("ville")
    public String getVille() {
        return ville;
    }

    public void setVille(String ville) {
        this.ville = ville;
    }

    @JsonbProperty("codePostal")
    public String getCodePostal() {
        return codePostal;
    }

    public void setCodePostal(String codePostal) {
        this.codePostal = codePostal;
    }

    @JsonbProperty("telephone")
    public String getTelephone() {
        return telephone;
    }

    public void setTelephone(String telephone) {
        this.telephone = telephone;
    }

    @JsonbProperty("email")
    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    @JsonbProperty("valide")
    public Boolean getValide() {
        return valide;
    }

    public void setValide(Boolean valide) {
        this.valide = valide;
    }

    @JsonbProperty("contributeurId")
    public Long getContributeurId() {
        return contributeurId;
    }

    public void setContributeurId(Long contributeurId) {
        this.contributeurId = contributeurId;
    }

    @JsonbProperty("commentaireAdmin")
    public String getCommentaireAdmin() {
        return commentaireAdmin;
    }

    public void setCommentaireAdmin(String commentaireAdmin) {
        this.commentaireAdmin = commentaireAdmin;
    }

    @Override
    public String toString() {
        return "Ecole{" +
                "id=" + id +
                ", nom='" + nom + '\'' +
                ", adresse='" + adresse + '\'' +
                ", ville='" + ville + '\'' +
                ", codePostal='" + codePostal + '\'' +
                ", telephone='" + telephone + '\'' +
                ", email='" + email + '\'' +
                ", valide=" + valide +
                ", contributeurId=" + contributeurId +
                ", commentaireAdmin='" + commentaireAdmin + '\'' +
                '}';
    }
} 