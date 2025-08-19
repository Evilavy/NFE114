package com.demo;
 
import jakarta.ws.rs.*; // import de @Path, @GET, @Produces
import jakarta.ws.rs.core.MediaType; // import de MediaType
 
// Cette classe est un endpoint pour notre API REST
@Path("/hello")
public class HelloResource {
    @GET // méthode HTTP GET
    @Produces(MediaType.TEXT_PLAIN) // ce que notre méthode produit (ici du texte brut)
    public String hello() {
        return "Hello from Jakarta EE"; // réponse de la méthode
    }
 
}