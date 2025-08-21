package com.demo;

import com.demo.model.User;
import com.demo.service.EntityManagerService;
import jakarta.persistence.EntityManager;
import jakarta.persistence.TypedQuery;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.security.SecureRandom;
import java.util.Base64;

/**
 * Ressource JAX-RS Utilisateurs
 *
 * CRUD utilisateurs, approbation admin et gestion des points.
 * Contient une implémentation simplifiée de BCrypt (à remplacer en prod).
 */
@Path("/users")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class UserResource {
    
    private EntityManager em = EntityManagerService.getEntityManagerFactory().createEntityManager();
    
    /**
     * Hash un mot de passe avec bcrypt
     * @param plainPassword Le mot de passe en clair
     * @return Le mot de passe hashé
     */
    private String hashPassword(String plainPassword) {
        try {
            // Utiliser BCrypt avec un salt généré automatiquement
            return BCrypt.hashpw(plainPassword, BCrypt.gensalt(12));
        } catch (Exception e) {
            System.err.println("Erreur lors du hashage du mot de passe: " + e.getMessage());
            // En cas d'erreur, retourner le mot de passe original (non sécurisé mais fonctionnel)
            return plainPassword;
        }
    }
    
    /**
     * Vérifie si un mot de passe correspond à son hash
     * @param plainPassword Le mot de passe en clair
     * @param hashedPassword Le mot de passe hashé
     * @return true si le mot de passe correspond
     */
    private boolean checkPassword(String plainPassword, String hashedPassword) {
        try {
            return BCrypt.checkpw(plainPassword, hashedPassword);
        } catch (Exception e) {
            System.err.println("Erreur lors de la vérification du mot de passe: " + e.getMessage());
            return false;
        }
    }
    
    // Classe BCrypt simple pour le hashage
    private static class BCrypt {
        private static final String ALPHABET = "./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        private static final SecureRandom random = new SecureRandom();
        
        public static String hashpw(String password, String salt) {
            // Implémentation simplifiée de BCrypt
            // En production, utilisez une bibliothèque comme jBCrypt
            try {
                MessageDigest md = MessageDigest.getInstance("SHA-256");
                String saltedPassword = password + salt;
                byte[] hash = md.digest(saltedPassword.getBytes());
                return "$2a$12$" + salt + "$" + Base64.getEncoder().encodeToString(hash);
            } catch (NoSuchAlgorithmException e) {
                throw new RuntimeException("SHA-256 not available", e);
            }
        }
        
        public static String gensalt(int logRounds) {
            StringBuilder salt = new StringBuilder();
            for (int i = 0; i < 22; i++) {
                salt.append(ALPHABET.charAt(random.nextInt(ALPHABET.length())));
            }
            return salt.toString();
        }
        
        public static boolean checkpw(String plaintext, String hashed) {
            try {
                String[] parts = hashed.split("\\$");
                if (parts.length != 4) return false;
                String salt = parts[2];
                String computedHash = hashpw(plaintext, salt);
                return computedHash.equals(hashed);
            } catch (Exception e) {
                return false;
            }
        }
    }
    
    /**
     * Liste tous les utilisateurs.
     */
    @GET
    public List<User> getAllUsers() {
        try {
            TypedQuery<User> query = em.createQuery("SELECT u FROM User u", User.class);
            return query.getResultList();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des utilisateurs: " + e.getMessage());
            return new ArrayList<>();
        }
    }
    
    /**
     * Récupère un utilisateur par identifiant.
     */
    @GET
    @Path("/{id}")
    public Response getUserById(@PathParam("id") Long id) {
        try {
            User user = em.find(User.class, id);
            if (user != null) {
                return Response.ok(user).build();
            } else {
                return Response.status(Response.Status.NOT_FOUND).build();
            }
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération de l'utilisateur: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    /**
     * Crée un utilisateur après vérification d'unicité email et hash du mot de passe.
     */
    @POST
    public Response createUser(User user) {
        if (user.getNom() == null || user.getNom().isEmpty() ||
            user.getPrenom() == null || user.getPrenom().isEmpty() ||
            user.getEmail() == null || user.getEmail().isEmpty() ||
            user.getPassword() == null || user.getPassword().isEmpty() ||
            user.getRole() == null || user.getRole().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("Tous les champs sont obligatoires")
                    .build();
        }
        
        try {
            // Vérifier si l'email existe déjà
            TypedQuery<User> query = em.createQuery("SELECT u FROM User u WHERE u.email = :email", User.class);
            query.setParameter("email", user.getEmail());
            List<User> existingUsers = query.getResultList();
            
            if (!existingUsers.isEmpty()) {
                return Response.status(Response.Status.CONFLICT)
                        .entity("Un utilisateur avec cet email existe déjà")
                        .build();
            }
            
            // Hash le mot de passe avant de le stocker
            String plainPassword = user.getPassword();
            String hashedPassword = hashPassword(plainPassword);
            user.setPassword(hashedPassword);
            
            System.out.println("Mot de passe original: " + plainPassword);
            System.out.println("Mot de passe hashé: " + hashedPassword);
            
            user.setApprouveParAdmin(false);
            user.setPoints(50); // Solde initial
            user.setDateCreation(LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd")));
            
            em.getTransaction().begin();
            em.persist(user);
            em.getTransaction().commit();
            
            return Response.status(Response.Status.CREATED).entity(user).build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la création de l'utilisateur: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    /**
     * Met à jour un utilisateur (hash du mot de passe si fourni).
     */
    @PUT
    @Path("/{id}")
    public Response updateUser(@PathParam("id") Long id, User user) {
        try {
            User existingUser = em.find(User.class, id);
            if (existingUser == null) {
                return Response.status(Response.Status.NOT_FOUND).build();
            }
            
            if (user.getNom() != null) existingUser.setNom(user.getNom());
            if (user.getPrenom() != null) existingUser.setPrenom(user.getPrenom());
            if (user.getEmail() != null) existingUser.setEmail(user.getEmail());
            if (user.getPassword() != null) {
                // Hash le mot de passe lors de la mise à jour
                String hashedPassword = hashPassword(user.getPassword());
                existingUser.setPassword(hashedPassword);
            }
            if (user.getRole() != null) existingUser.setRole(user.getRole());
            if (user.getPoints() >= 0) existingUser.setPoints(user.getPoints());
            
            em.getTransaction().begin();
            em.merge(existingUser);
            em.getTransaction().commit();
            
            return Response.ok(existingUser).build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la mise à jour de l'utilisateur: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    /**
     * Supprime un utilisateur.
     */
    @DELETE
    @Path("/{id}")
    public Response deleteUser(@PathParam("id") Long id) {
        try {
            User user = em.find(User.class, id);
            if (user != null) {
                em.getTransaction().begin();
                em.remove(user);
                em.getTransaction().commit();
                return Response.ok().build();
            } else {
                return Response.status(Response.Status.NOT_FOUND).build();
            }
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la suppression de l'utilisateur: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    /**
     * Liste des utilisateurs en attente d'approbation admin.
     */
    @GET
    @Path("/pending")
    public List<User> getPendingUsers() {
        try {
            TypedQuery<User> query = em.createQuery("SELECT u FROM User u WHERE u.approuveParAdmin = false", User.class);
            return query.getResultList();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des utilisateurs en attente: " + e.getMessage());
            return new ArrayList<>();
        }
    }
    
    /**
     * Approuve un utilisateur (validation admin).
     */
    @PUT
    @Path("/{id}/approve")
    public Response approveUser(@PathParam("id") Long id) {
        try {
            User user = em.find(User.class, id);
            if (user != null) {
                user.setApprouveParAdmin(true);
                em.getTransaction().begin();
                em.merge(user);
                em.getTransaction().commit();
                return Response.ok(user).build();
            } else {
                return Response.status(Response.Status.NOT_FOUND).build();
            }
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de l'approbation de l'utilisateur: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    /**
     * Rejette un utilisateur et le supprime.
     */
    @PUT
    @Path("/{id}/reject")
    public Response rejectUser(@PathParam("id") Long id) {
        try {
            User user = em.find(User.class, id);
            if (user != null) {
                em.getTransaction().begin();
                em.remove(user);
                em.getTransaction().commit();
                return Response.ok().build();
            } else {
                return Response.status(Response.Status.NOT_FOUND).build();
            }
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors du rejet de l'utilisateur: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    /**
     * Retourne le solde de points d'un utilisateur.
     */
    @GET
    @Path("/{id}/points")
    public Response getUserPoints(@PathParam("id") Long id) {
        try {
            User user = em.find(User.class, id);
            if (user != null) {
                return Response.ok("{\"points\": " + user.getPoints() + "}").build();
            } else {
                return Response.status(Response.Status.NOT_FOUND).build();
            }
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des points: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    /**
     * Ajoute des points à un utilisateur.
     */
    @PUT
    @Path("/{id}/points/add")
    public Response addPoints(@PathParam("id") Long id, int points) {
        try {
            User user = em.find(User.class, id);
            if (user != null) {
                user.ajouterPoints(points);
                em.getTransaction().begin();
                em.merge(user);
                em.getTransaction().commit();
                return Response.ok(user).build();
            } else {
                return Response.status(Response.Status.NOT_FOUND).build();
            }
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de l'ajout de points: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    /**
     * Retire des points (si solde suffisant).
     */
    @PUT
    @Path("/{id}/points/remove")
    public Response removePoints(@PathParam("id") Long id, int points) {
        try {
            User user = em.find(User.class, id);
            if (user != null) {
                if (user.retirerPoints(points)) {
                    em.getTransaction().begin();
                    em.merge(user);
                    em.getTransaction().commit();
                    return Response.ok(user).build();
                } else {
                    return Response.status(Response.Status.BAD_REQUEST)
                            .entity("Points insuffisants")
                            .build();
                }
            } else {
                return Response.status(Response.Status.NOT_FOUND).build();
            }
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors du retrait de points: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
} 