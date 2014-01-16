<?php

namespace eoko\module\executor;

/**
 * This interface marks {@link Executor} which are intended only for internal 
 * execution of their actions, as opposed to execution triggered by the 
 * {@link Request}.
 * 
 * If a Request happens to resolve to one of this Executor's action, a
 * SecurityException will be thrown.
 * 
 * As opposed to other Executors, the ones that are marked by this interface
 * must not prefix their internal actions with an underscore (_).
 */
interface InternalExecutor {

}
