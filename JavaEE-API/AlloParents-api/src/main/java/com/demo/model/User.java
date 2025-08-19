package com.demo.model;

import jakarta.json.bind.annotation.JsonbProperty;
import jakarta.persistence.*;

@Entity
@Table(name = "user")
public class User {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    
    @Column(name = "nom", nullable = false)
    private String nom;
    
    @Column(name = "prenom", nullable = false)
    private String prenom;
    
    @Column(name = "email", nullable = false, unique = true)
    private String email;
    
    @Column(name = "password", nullable = false)
    private String password;
    
    @Column(name = "role", nullable = false)
    private String role;
    
    @Column(name = "is_approved_by_admin", nullable = false)
    private boolean approuveParAdmin;
    
    @Column(name = "points", nullable = false)
    private int points;
    
    @Column(name = "created_at")
    private String dateCreation;

    public User() {
        this.points = 50; // Solde initial de 50 points
    }

    public User(Long id, String nom, String prenom, String email, String password, String role, boolean approuveParAdmin, int points, String dateCreation) {
        this.id = id;
        this.nom = nom;
        this.prenom = prenom;
        this.email = email;
        this.password = password;
        this.role = role;
        this.approuveParAdmin = approuveParAdmin;
        this.points = points;
        this.dateCreation = dateCreation;
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

    @JsonbProperty("prenom")
    public String getPrenom() {
        return prenom;
    }

    public void setPrenom(String prenom) {
        this.prenom = prenom;
    }

    @JsonbProperty("email")
    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    @JsonbProperty("password")
    public String getPassword() {
        return password;
    }

    public void setPassword(String password) {
        this.password = password;
    }

    @JsonbProperty("role")
    public String getRole() {
        return role;
    }

    public void setRole(String role) {
        this.role = role;
    }

    @JsonbProperty("approuveParAdmin")
    public boolean isApprouveParAdmin() {
        return approuveParAdmin;
    }

    public void setApprouveParAdmin(boolean approuveParAdmin) {
        this.approuveParAdmin = approuveParAdmin;
    }

    @JsonbProperty("points")
    public int getPoints() {
        return points;
    }

    public void setPoints(int points) {
        this.points = points;
    }

    @JsonbProperty("dateCreation")
    public String getDateCreation() {
        return dateCreation;
    }

    public void setDateCreation(String dateCreation) {
        this.dateCreation = dateCreation;
    }

    // Méthodes pour gérer les points
    public void ajouterPoints(int points) {
        this.points += points;
    }

    public boolean retirerPoints(int points) {
        if (this.points >= points) {
            this.points -= points;
            return true;
        }
        return false;
    }

    public boolean aSuffisammentDePoints(int points) {
        return this.points >= points;
    }
} 